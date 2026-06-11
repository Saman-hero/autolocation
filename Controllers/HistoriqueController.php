<?php
/**
 * Controllers/HistoriqueController.php
 *
 * Displays the full searchable history of all reservations regardless of status.
 * This is a read-only view — no write actions are performed here.
 *
 * Unlike ReservationController::index() which is the operational view (used daily
 * to manage active bookings), this controller is the historical archive — useful
 * for retrospective analysis, audits, and client history lookups.
 *
 * All filter combinations are handled with named PDO placeholders to prevent
 * SQL injection in the dynamic WHERE clause.
 */
require_once __DIR__ . "/../config/database.php";

class HistoriqueController {

    /** @var PDO */
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET — Full reservation history with multi-criteria filtering.
     *
     * Query params:
     *   q          — text search across reference, client name, vehicle number.
     *   client_id  — filter by specific client.
     *   vehicle_id — filter by specific vehicle.
     *   from       — start date lower bound (DATE comparison on date_debut).
     *   to         — start date upper bound.
     *   statut     — exact status match.
     *
     * Results include client and vehicle display data via JOINs.
     * No pagination — all matching records are returned (consider adding for large datasets).
     */
    public function index() {
        $search    = trim($_GET['q']          ?? '');
        $clientId  = (int)($_GET['client_id'] ?? 0);
        $vehicleId = (int)($_GET['vehicle_id'] ?? 0);
        $from      = $_GET['from'] ?? '';
        $to        = $_GET['to']   ?? '';
        $statut    = $_GET['statut'] ?? '';

        $clients  = $this->conn->query("SELECT id, nom, prenom FROM clients ORDER BY nom")->fetchAll();
        $vehicles = $this->conn->query("SELECT id, numero FROM vehicles ORDER BY numero")->fetchAll();

        $sql = "SELECT r.*, c.nom AS client_nom, c.prenom AS client_prenom,
                       v.numero AS vehicle_numero, v.marque, v.modele
                FROM reservations r
                JOIN clients c ON r.client_id = c.id
                JOIN vehicles v ON r.vehicle_id = v.id
                WHERE 1=1";
        $params = [];

        if ($search)    { $sql .= " AND (r.reference LIKE :q OR c.nom LIKE :q OR c.prenom LIKE :q OR v.numero LIKE :q)"; $params[':q'] = "%$search%"; }
        if ($clientId)  { $sql .= " AND r.client_id = :cid";  $params[':cid']    = $clientId; }
        if ($vehicleId) { $sql .= " AND r.vehicle_id = :vid";  $params[':vid']    = $vehicleId; }
        if ($from)      { $sql .= " AND DATE(r.date_debut) >= :from"; $params[':from'] = $from; }
        if ($to)        { $sql .= " AND DATE(r.date_debut) <= :to";   $params[':to']   = $to; }
        if ($statut)    { $sql .= " AND r.statut = :statut";  $params[':statut'] = $statut; }
        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $reservations = $stmt->fetchAll();

        $rBadge = [
            'en attente' => 'bg-secondary', 'confirmée' => 'bg-primary',
            'en cours'   => 'badge-encours', 'terminée'  => 'badge-terminee',
            'annulée'    => 'badge-annulee',
        ];

        require __DIR__ . "/../Views/historique/index.php";
    }
}
