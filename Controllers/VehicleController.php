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
        $categorie = $_GET['categorie'] ?? '';
        $statut    = $_GET['statut'] ?? '';

        // Pass null instead of empty string so the model skips the WHERE clause.
        $vehicles   = $this->model->search($keyword ?: null, $categorie ?: null, $statut ?: null);
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
            $this->model->create([
                ':numero'               => trim($_POST['numero']),
                ':immatriculation'      => trim($_POST['immatriculation']) ?: null,
                ':marque'               => trim($_POST['marque']),
                ':modele'               => trim($_POST['modele']),
                ':annee'                => (int)$_POST['annee'],
                ':couleur'              => trim($_POST['couleur']) ?: null,
                ':nb_places'            => (int)($_POST['nb_places'] ?: 5),    // Default 5 seats
                ':categorie'            => $_POST['categorie'],
                ':kilometrage'          => (int)($_POST['kilometrage'] ?: 0),
                ':statut'               => $_POST['statut'],
                ':prix_jour'            => (float)($_POST['prix_jour'] ?: 0),
                ':caution'              => (float)($_POST['caution'] ?: 0),
                ':type_vidange'         => $_POST['type_vidange'] ?: null,
                ':intervalle_vidange'   => (int)($_POST['intervalle_vidange'] ?: 10000), // Default 10 000 km
                // Store NULL if the field was left blank (vehicle may not have a recorded oil change yet).
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
            $this->model->update([
                ':id'                   => $id,
                ':numero'               => trim($_POST['numero']),
                ':immatriculation'      => trim($_POST['immatriculation']) ?: null,
                ':marque'               => trim($_POST['marque']),
                ':modele'               => trim($_POST['modele']),
                ':annee'                => (int)$_POST['annee'],
                ':couleur'              => trim($_POST['couleur']) ?: null,
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
