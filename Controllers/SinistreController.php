<?php
/**
 * Controllers/SinistreController.php
 *
 * Manages incident and accident reports (sinistres) linked to vehicles:
 *   index()  — filterable list with total repair cost.
 *   add()    — declare a new incident (supports pre-linking to a reservation).
 *   delete() — remove an incident record.
 *
 * Incident references follow the format: SIN-YYYY-NNN (e.g. SIN-2026-003).
 * The reference is auto-generated but can be overridden in the form.
 *
 * Each sinistre can optionally be linked to:
 *   - A specific reservation (reservation_id).
 *   - A specific client (client_id).
 *
 * Types: accident, bris de vitre, vol, dommage, autre.
 * Statuses: ouvert, en cours de traitement, fermé.
 */
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/audit.php";

class SinistreController {

    /** @var PDO */
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET — List all incidents with optional type/status filters.
     *
     * Joins with vehicles, clients (LEFT JOIN — client may be null),
     * and reservations (LEFT JOIN — not all incidents are tied to a reservation).
     *
     * $totalCout is the sum of repair costs across all displayed incidents,
     * shown as a summary at the bottom of the list.
     */
    public function index() {
        $filterType   = $_GET['type']   ?? '';
        $filterStatut = $_GET['statut'] ?? '';

        $sql = "
            SELECT s.*, v.numero AS vehicle_numero, v.marque, v.modele,
                   c.nom AS client_nom, c.prenom AS client_prenom,
                   r.reference AS res_ref
            FROM sinistres s
            JOIN vehicles v ON s.vehicle_id = v.id
            LEFT JOIN clients c ON s.client_id = c.id
            LEFT JOIN reservations r ON s.reservation_id = r.id
            WHERE 1=1
        ";
        $params = [];
        if ($filterType)   { $sql .= " AND s.type = ?";   $params[] = $filterType; }
        if ($filterStatut) { $sql .= " AND s.statut = ?";  $params[] = $filterStatut; }
        $sql .= " ORDER BY s.date_sinistre DESC, s.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $sinistres  = $stmt->fetchAll();
        $totalCout  = array_sum(array_column($sinistres, 'cout_reparation'));

        require __DIR__ . "/../Views/sinistres/index.php";
    }

    /**
     * GET  — Show the declare-incident form.
     * POST — Validate and insert the incident record.
     *
     * Pre-selection via query params (all optional):
     *   ?reservation_id=X — links the incident to a specific reservation.
     *   ?vehicle_id=X     — pre-selects the vehicle.
     *   ?client_id=X      — pre-selects the client.
     *
     * Auto-reference: SIN-YYYY-NNN, computed from the last existing reference
     * this year (same pattern as the reservation reference generator).
     * The operator can override it in the form.
     *
     * After saving, redirects to the reservation view (if linked) or the
     * incident list (if standalone).
     */
    public function add() {
        $vehicles     = $this->conn->query("SELECT id, numero, marque, modele FROM vehicles ORDER BY numero")->fetchAll();
        $clients      = $this->conn->query("SELECT id, nom, prenom FROM clients ORDER BY nom")->fetchAll();

        // Pre-selection values from query string.
        $preResId     = (int)($_GET['reservation_id'] ?? 0);
        $preVehicleId = (int)($_GET['vehicle_id']     ?? 0);
        $preClientId  = (int)($_GET['client_id']      ?? 0);
        $errors       = [];

        // Auto-generate the next SIN reference for this year.
        $year    = date('Y');
        $lastRef = $this->conn->query("SELECT reference FROM sinistres WHERE reference LIKE 'SIN-$year-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
        $nextNum = $lastRef ? (int)end(explode('-', $lastRef)) + 1 : 1;
        $autoRef = sprintf('SIN-%s-%03d', $year, $nextNum);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehicleId = (int)$_POST['vehicle_id'];
            if (!$vehicleId)        $errors[] = 'Sélectionner un véhicule.';
            if (empty($_POST['type'])) $errors[] = 'Type requis.';

            if (!$errors) {
                $this->conn->prepare("
                    INSERT INTO sinistres
                        (reference, reservation_id, vehicle_id, client_id, type, description,
                         cout_reparation, prise_en_charge, date_sinistre, statut)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                    trim($_POST['reference']) ?: $autoRef,
                    $_POST['reservation_id'] ?: null,
                    $vehicleId,
                    $_POST['client_id'] ?: null,
                    $_POST['type'],
                    trim($_POST['description']) ?: null,
                    $_POST['cout_reparation'] !== '' ? (float)$_POST['cout_reparation'] : null,
                    $_POST['prise_en_charge'],
                    $_POST['date_sinistre'] ?: null,
                    $_POST['statut'],
                ]);
                flash('success', 'Sinistre déclaré avec succès.');
                header($preResId ? "Location: /location/public/index.php?url=reservations/view&id=$preResId" : "Location: /location/public/index.php?url=sinistres");
                exit;
            }
        }

        require __DIR__ . "/../Views/sinistres/add.php";
    }

    /**
     * GET — Delete an incident record by PK and redirect to the incident list.
     * No cascade effects — incidents are not linked to vehicle status.
     */
    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $this->conn->prepare("DELETE FROM sinistres WHERE id=?")->execute([$id]);
            flash('success', 'Sinistre supprimé.');
        }
        header("Location: /location/public/index.php?url=sinistres");
        exit;
    }
}
