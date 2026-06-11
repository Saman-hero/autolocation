<?php
/**
 * Controllers/MaintenanceController.php
 *
 * Manages maintenance records for the vehicle fleet:
 *   index()  — filterable list of all maintenance entries.
 *   add()    — log a new maintenance event.
 *   edit()   — update an existing maintenance record.
 *   delete() — remove a maintenance entry.
 *
 * Side effects on vehicle status (automatically managed):
 *   - When a record moves to 'planifiée' or 'en cours': vehicle → 'maintenance'
 *     (only if currently 'disponible' — avoids overwriting 'loué').
 *   - When a record is marked 'terminée': vehicle → 'disponible'
 *     (only if currently 'maintenance').
 *
 * Oil-change special case (add/edit):
 *   If the maintenance type is 'vidange' AND status is 'terminée',
 *   the vehicle's derniere_vidange_km and date_derniere_vidange are updated.
 *   This feeds the dashboard oil-change alert.
 */
require_once __DIR__ . "/../config/database.php";

class MaintenanceController {

    /** @var PDO */
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET — List maintenance records with optional vehicle/status filters.
     * Results join with vehicles so each row includes the vehicle's number and name.
     */
    public function index() {
        $filterVehicle = (int)($_GET['vehicle_id'] ?? 0);
        $filterStatut  = $_GET['statut'] ?? '';
        $where = []; $params = [];

        if ($filterVehicle) { $where[] = "m.vehicle_id = ?"; $params[] = $filterVehicle; }
        if ($filterStatut)  { $where[] = "m.statut = ?";     $params[] = $filterStatut; }

        $sql = "SELECT m.*, v.numero, v.marque, v.modele FROM maintenance m
                JOIN vehicles v ON m.vehicle_id = v.id"
             . ($where ? " WHERE " . implode(" AND ", $where) : "")
             . " ORDER BY m.date_maintenance DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $records  = $stmt->fetchAll();
        $vehicles = $this->conn->query("SELECT id, numero, marque, modele FROM vehicles ORDER BY numero")->fetchAll();

        require __DIR__ . "/../Views/maintenance/index.php";
    }

    /**
     * GET  — Show the add-maintenance form (pre-selects vehicle if ?vehicle_id is given).
     * POST — Insert the record and apply side effects to vehicle status.
     *
     * Vehicle status side effects:
     *   'planifiée'|'en cours' → vehicle set to 'maintenance' (if currently 'disponible').
     *   'terminée'             → vehicle set to 'disponible' (if currently 'maintenance').
     *
     * Oil-change special case:
     *   If type='vidange' and status='terminée', update the vehicle's oil-change km.
     */
    public function add() {
        // Include current kilometrage so the form can suggest the intervention km.
        $vehicles     = $this->conn->query("SELECT id, numero, marque, modele, kilometrage FROM vehicles ORDER BY numero")->fetchAll();
        $preVehicleId = (int)($_GET['vehicle_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehicleId = (int)$_POST['vehicle_id'];
            $type      = trim($_POST['type_maintenance']);
            $statut    = $_POST['statut'];
            // km at intervention is optional — NULL if not provided.
            $km        = $_POST['kilometrage_intervention'] !== '' ? (int)$_POST['kilometrage_intervention'] : null;
            $date      = $_POST['date_maintenance'];

            $this->conn->prepare("
                INSERT INTO maintenance
                    (vehicle_id, type_maintenance, description, date_maintenance,
                     kilometrage_intervention, cout, technicien, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $vehicleId, $type,
                trim($_POST['description'] ?? ''), $date, $km,
                $_POST['cout'] !== '' ? (float)$_POST['cout'] : null, // Cost is optional
                trim($_POST['technicien'] ?? ''), $statut,
            ]);

            // If this is a completed oil change, update the vehicle's oil-change tracking fields.
            if ($statut === 'terminée' && $km && strtolower($type) === 'vidange') {
                $this->conn->prepare("UPDATE vehicles SET derniere_vidange_km=?, date_derniere_vidange=? WHERE id=?")
                     ->execute([$km, $date, $vehicleId]);
            }

            // Keep vehicle status in sync with the maintenance status.
            // Only update if the vehicle is in the expected state to avoid race conditions.
            if (in_array($statut, ['planifiée','en cours'])) {
                $this->conn->prepare("UPDATE vehicles SET statut='maintenance' WHERE id=? AND statut='disponible'")->execute([$vehicleId]);
            }
            if ($statut === 'terminée') {
                $this->conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=? AND statut='maintenance'")->execute([$vehicleId]);
            }

            flash('success', 'Maintenance enregistrée avec succès.');
            header("Location: /location/public/index.php?url=maintenance");
            exit;
        }

        require __DIR__ . "/../Views/maintenance/add.php";
    }

    /**
     * GET  — Show the edit form pre-filled with the existing maintenance record.
     * POST — Apply changes and update vehicle status based on old → new status transition.
     *
     * Status transition logic:
     *   terminée → planifiée/en cours : vehicle set back to 'maintenance'.
     *   planifiée/en cours → terminée : vehicle set to 'disponible'.
     */
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=maintenance"); exit; }

        $stmt = $this->conn->prepare("SELECT * FROM maintenance WHERE id=?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        if (!$record) { header("Location: /location/public/index.php?url=maintenance"); exit; }

        $vehicles = $this->conn->query("SELECT id, numero, marque, modele, kilometrage FROM vehicles ORDER BY numero")->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehicleId = (int)$_POST['vehicle_id'];
            $type      = trim($_POST['type_maintenance']);
            $statut    = $_POST['statut'];
            $oldStatut = $record['statut'];
            $km        = $_POST['kilometrage_intervention'] !== '' ? (int)$_POST['kilometrage_intervention'] : null;
            $date      = $_POST['date_maintenance'];

            $this->conn->prepare("
                UPDATE maintenance SET vehicle_id=?, type_maintenance=?, description=?,
                    date_maintenance=?, kilometrage_intervention=?, cout=?, technicien=?, statut=?
                WHERE id=?
            ")->execute([
                $vehicleId, $type, trim($_POST['description'] ?? ''), $date, $km,
                $_POST['cout'] !== '' ? (float)$_POST['cout'] : null,
                trim($_POST['technicien'] ?? ''), $statut, $id,
            ]);

            if ($statut === 'terminée' && $km && strtolower($type) === 'vidange') {
                $this->conn->prepare("UPDATE vehicles SET derniere_vidange_km=?, date_derniere_vidange=? WHERE id=?")
                     ->execute([$km, $date, $vehicleId]);
            }
            if ($statut === 'terminée' && $oldStatut !== 'terminée') {
                $this->conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=? AND statut='maintenance'")->execute([$vehicleId]);
            } elseif (in_array($statut, ['planifiée','en cours']) && $oldStatut === 'terminée') {
                $this->conn->prepare("UPDATE vehicles SET statut='maintenance' WHERE id=? AND statut='disponible'")->execute([$vehicleId]);
            }

            flash('success', 'Maintenance mise à jour.');
            header("Location: /location/public/index.php?url=maintenance");
            exit;
        }

        require __DIR__ . "/../Views/maintenance/edit.php";
    }

    /**
     * GET — Delete a maintenance record and, if it was active, restore the vehicle to 'disponible'.
     *
     * If the deleted record had status 'planifiée' or 'en cours', the vehicle was
     * set to 'maintenance' when the record was created. Deleting it should release
     * the vehicle back to 'disponible'.
     */
    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=maintenance"); exit; }

        $stmt = $this->conn->prepare("SELECT * FROM maintenance WHERE id=?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();

        if ($record) {
            // If the record was active, release the vehicle back to available.
            if (in_array($record['statut'], ['planifiée','en cours'])) {
                $this->conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=? AND statut='maintenance'")->execute([$record['vehicle_id']]);
            }
            $this->conn->prepare("DELETE FROM maintenance WHERE id=?")->execute([$id]);
            flash('success', 'Maintenance supprimée.');
        }

        header("Location: /location/public/index.php?url=maintenance");
        exit;
    }
}
