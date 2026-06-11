<?php
/**
 * Controllers/VehicleController.php
 *
 * Handles all HTTP actions for the vehicle (fleet) module:
 *   index()  — searchable, filterable fleet list.
 *   add()    — register a new vehicle.
 *   edit()   — update vehicle details (including oil-change data).
 *   delete() — remove a vehicle from the fleet.
 *
 * Write actions (edit, delete) are logged via audit_log().
 * Note: add() does not currently log a CREATE entry — consider adding it.
 */
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/VehicleModel.php";
require_once __DIR__ . "/../includes/audit.php";

class VehicleController {

    /** @var PDO */
    private $conn;

    /** @var VehicleModel */
    private $model;

    public function __construct() {
        $db = new Database();
        $this->conn  = $db->getConnection();
        $this->model = new VehicleModel($this->conn);
    }

    /**
     * GET — Display the fleet list with optional search and category/status filters.
     *
     * Query params:
     *   q         — keyword search (numero, marque, modele, immatriculation).
     *   categorie — exact category filter ('SUV', 'berline' …).
     *   statut    — exact status filter ('disponible', 'loué' …).
     *
     * $categories is passed to the view to populate the category filter dropdown.
     */
    public function index() {
        $keyword   = trim($_GET['q'] ?? '');
        $modele    = trim($_GET['modele'] ?? '');
        $categorie = $_GET['categorie'] ?? '';
        $statut    = $_GET['statut'] ?? '';

        // Pass null instead of empty string so the model skips the WHERE clause.
        $vehicles   = $this->model->search($keyword ?: null, $categorie ?: null, $statut ?: null, $modele ?: null);
        $categories = ['économique','berline','SUV','premium','utilitaire']; // Dropdown options
        require __DIR__ . "/../Views/vehicles/index.php";
    }

    /**
     * GET  — Show the add-vehicle form.
     * POST — Save the new vehicle without validation (all fields treated as trusted).
     *
     * Type coercion notes:
     *   - Numeric fields (annee, kilometrage, nb_places …) are cast to int/float.
     *   - derniere_vidange_km is nullable — stored as NULL if the field is blank.
     *   - Empty string fields → NULL via ternary (avoids storing "" in the DB).
     */
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle image upload
            $imageName = null;
            if (!empty($_FILES['image']['name'])) {
                $imageName = $this->uploadImage($_FILES['image']);
            }

            $this->model->create([
                ':numero'               => trim($_POST['numero']),
                ':immatriculation'      => trim($_POST['immatriculation']) ?: null,
                ':marque'               => trim($_POST['marque']),
                ':modele'               => trim($_POST['modele']),
                ':annee'                => (int)$_POST['annee'],
                ':couleur'              => trim($_POST['couleur']) ?: null,
                ':image'                => $imageName,
                ':nb_places'            => (int)($_POST['nb_places'] ?: 5),
                ':categorie'            => $_POST['categorie'],
                ':kilometrage'          => (int)($_POST['kilometrage'] ?: 0),
                ':statut'               => $_POST['statut'],
                ':prix_jour'            => (float)($_POST['prix_jour'] ?: 0),
                ':caution'              => (float)($_POST['caution'] ?: 0),
                ':type_vidange'         => $_POST['type_vidange'] ?: null,
                ':intervalle_vidange'   => (int)($_POST['intervalle_vidange'] ?: 10000),
                ':derniere_vidange_km'  => $_POST['derniere_vidange_km'] !== '' ? (int)$_POST['derniere_vidange_km'] : null,
                ':date_derniere_vidange'=> $_POST['date_derniere_vidange'] ?: null,
            ]);
            flash('success', 'Véhicule ajouté avec succès.');
            header("Location: /location/public/index.php?url=vehicles");
            exit;
        }

        require __DIR__ . "/../Views/vehicles/add.php";
    }

    /**
     * Upload vehicle image and return the filename.
     *
     * @param array $file $_FILES['image'] data
     * @return string|null Filename stored in DB or null on failure
     */
    private function uploadImage(array $file): ?string {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            flash('danger', 'L\'image est trop volumineuse (max 5MB).');
            return null;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            flash('danger', 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
            return null;
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'vehicle_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($extension);

        // Move file to uploads directory
        $uploadDir = __DIR__ . '/../uploads/vehicles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            return $filename;
        }

        return null;
    }

    /**
     * GET  — Show the edit form pre-filled with the existing vehicle data.
     * POST — Validate (minimal) and apply the updates.
     *
     * Additional view variables:
     *   $kmSince    — km driven since last oil change (for the oil-change progress bar).
     *   $intervalle — oil-change interval in km (for the progress bar calculation).
     *
     * Query params:
     *   id — Vehicle PK (required).
     */
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=vehicles"); exit; }

        $v = $this->model->getById($id);
        if (!$v) { header("Location: /location/public/index.php?url=vehicles"); exit; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle image upload
            $imageName = $v['image'] ?? null; // Keep existing image by default
            if (!empty($_FILES['image']['name'])) {
                // Delete old image if exists
                if (!empty($v['image'])) {
                    $oldImagePath = __DIR__ . '/../uploads/vehicles/' . $v['image'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imageName = $this->uploadImage($_FILES['image']);
            }
            // Handle image deletion
            if (isset($_POST['delete_image']) && $_POST['delete_image'] === '1') {
                if (!empty($v['image'])) {
                    $oldImagePath = __DIR__ . '/../uploads/vehicles/' . $v['image'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imageName = null;
            }

            $this->model->update([
                ':id'                   => $id,
                ':numero'               => trim($_POST['numero']),
                ':immatriculation'      => trim($_POST['immatriculation']) ?: null,
                ':marque'               => trim($_POST['marque']),
                ':modele'               => trim($_POST['modele']),
                ':annee'                => (int)$_POST['annee'],
                ':couleur'              => trim($_POST['couleur']) ?: null,
                ':image'                => $imageName,
                ':nb_places'            => (int)($_POST['nb_places'] ?: 5),
                ':categorie'            => $_POST['categorie'],
                ':kilometrage'          => (int)($_POST['kilometrage'] ?: 0),
                ':statut'               => $_POST['statut'],
                ':prix_jour'            => (float)($_POST['prix_jour'] ?: 0),
                ':caution'              => (float)($_POST['caution'] ?: 0),
                ':type_vidange'         => $_POST['type_vidange'] ?: null,
                ':intervalle_vidange'   => (int)($_POST['intervalle_vidange'] ?: 10000),
                ':derniere_vidange_km'  => $_POST['derniere_vidange_km'] !== '' ? (int)$_POST['derniere_vidange_km'] : null,
                ':date_derniere_vidange'=> $_POST['date_derniere_vidange'] ?: null,
            ]);
            audit_log($this->conn, 'UPDATE', 'vehicles', $id, "Véhicule modifié : {$v['numero']}");
            flash('success', 'Véhicule mis à jour.');
            header("Location: /location/public/index.php?url=vehicles");
            exit;
        }

        // Compute km driven since last oil change to show the progress bar in the edit view.
        $kmSince    = ($v['kilometrage'] ?? 0) - ($v['derniere_vidange_km'] ?? 0);
        $intervalle = $v['intervalle_vidange'] ?? 10000;
        require __DIR__ . "/../Views/vehicles/edit.php";
    }

    /**
     * GET — Delete a vehicle and redirect to the fleet list.
     *
     * The vehicle row is fetched before deletion so its numero can be
     * included in the audit log entry.
     *
     * Note: no check for linked reservations — this may break FK constraints
     * if cascade delete is not configured on the vehicles FK in reservations.
     *
     * Query params:
     *   id — Vehicle PK.
     */
    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $v = $this->model->getById($id);
            if ($v) {
                $this->model->delete($id);
                audit_log($this->conn, 'DELETE', 'vehicles', $id, "Véhicule supprimé : {$v['numero']}");
                flash('success', 'Véhicule supprimé.');
            }
        }
        header("Location: /location/public/index.php?url=vehicles");
        exit;
    }
}
