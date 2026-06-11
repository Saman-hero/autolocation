<?php
/**
 * Controllers/EtatVehiculeController.php
 *
 * Manages vehicle inspection reports (état véhicule) — pre/post rental checklists:
 *   index() — paginated list of all inspection sheets, filterable by type.
 *   add()   — create a new inspection report for a reservation.
 *   view()  — display a single inspection report in detail.
 *
 * Each inspection captures:
 *   type      — 'depart' (pre-rental) or 'retour' (post-rental).
 *   carburant — fuel level 0–8 (maps to labels: 0, 1/8, 1/4 … Plein).
 *   km        — odometer reading at inspection time.
 *   proprete  — cleanliness: 'propre' | 'moyen' | 'sale'.
 *   rayures   — checkbox: 1 if scratches noted, 0 otherwise.
 *   dommages  — free-text description of visible damage.
 *   notes     — additional observations.
 *
 * $fuelLabels maps the 0–8 integer fuel level to human-readable fractions.
 */
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/audit.php";

class EtatVehiculeController {

    /** @var PDO */
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * GET — Paginated list of all inspection reports, with optional type filter.
     *
     * Uses named PDO placeholders (:limit, :offset) bound as PDO::PARAM_INT
     * to ensure correct MySQL LIMIT/OFFSET handling (PDO treats them as strings
     * by default, which can break pagination in older MySQL/PHP versions).
     *
     * $fuelLabels is passed to the view to render the fuel level as a string.
     */
    public function index() {
        $conn       = $this->conn;
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $perPage    = 20;
        $offset     = ($page - 1) * $perPage;
        $typeFilter = $_GET['type'] ?? '';
        $where      = "WHERE 1=1";
        $params     = [];

        if ($typeFilter) { $where .= " AND ev.type = :type"; $params[':type'] = $typeFilter; }

        $countStmt = $conn->prepare("SELECT COUNT(*) FROM etat_vehicule ev $where");
        $countStmt->execute($params);
        $total  = (int)$countStmt->fetchColumn();
        $pages  = max(1, (int)ceil($total / $perPage));

        $params[':limit']  = $perPage;
        $params[':offset'] = $offset;

        $stmt = $conn->prepare("
            SELECT ev.id, ev.type, ev.carburant, ev.km, ev.proprete, ev.rayures, ev.created_at,
                   r.reference, r.id AS res_id, v.numero, v.marque, v.modele,
                   c.nom AS client_nom, c.prenom AS client_prenom
            FROM etat_vehicule ev
            JOIN reservations r ON ev.reservation_id = r.id
            JOIN vehicles v ON ev.vehicle_id = v.id
            JOIN clients c ON r.client_id = c.id
            $where ORDER BY ev.created_at DESC LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, in_array($k, [':limit',':offset']) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows       = $stmt->fetchAll();
        $fuelLabels = ['0','1/8','1/4','3/8','1/2','5/8','3/4','7/8','Plein'];

        require __DIR__ . "/../Views/etat-vehicule/index.php";
    }

    /**
     * GET  — Show the inspection form for a given reservation and type.
     * POST — Save the inspection report and log it.
     *
     * Required query params:
     *   reservation_id — links the report to a reservation.
     *   type           — 'depart' (outgoing) or 'retour' (incoming).
     *                    Defaults to 'depart' if an invalid value is provided.
     *
     * After saving, redirects to the reservation detail page.
     */
    public function add() {
        $conn          = $this->conn;
        $reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);

        // Whitelist the type to prevent arbitrary values being stored.
        $type          = in_array($_GET['type'] ?? $_POST['type'] ?? '', ['depart','retour'])
                         ? ($_GET['type'] ?? $_POST['type']) : 'depart';

        if (!$reservationId) { header("Location: /location/public/index.php?url=etat-vehicule"); exit; }

        $stmt = $conn->prepare("
            SELECT r.*, v.numero, v.marque, v.modele, v.kilometrage,
                   c.nom AS client_nom, c.prenom AS client_prenom
            FROM reservations r
            JOIN vehicles v ON r.vehicle_id = v.id
            JOIN clients c ON r.client_id = c.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reservationId]);
        $res = $stmt->fetch();
        if (!$res) { flash('danger', 'Réservation introuvable.'); header("Location: /location/public/index.php?url=etat-vehicule"); exit; }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $carburant = (int)($_POST['carburant'] ?? 4);
            $km        = $_POST['km'] !== '' ? (int)$_POST['km'] : null;
            $proprete  = in_array($_POST['proprete'] ?? '', ['propre','moyen','sale']) ? $_POST['proprete'] : 'propre';
            $rayures   = isset($_POST['rayures']) ? 1 : 0;

            $stmt = $conn->prepare("
                INSERT INTO etat_vehicule (reservation_id, vehicle_id, type, carburant, km, proprete, rayures, dommages, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reservationId, $res['vehicle_id'], $type, $carburant, $km, $proprete, $rayures,
                trim($_POST['dommages'] ?? '') ?: null,
                trim($_POST['notes'] ?? '') ?: null,
                $_SESSION['user_id'] ?? null,
            ]);
            $newId = (int)$conn->lastInsertId();
            audit_log($conn, 'CREATE', 'etat_vehicule', $newId, "État véhicule ($type) — réservation {$res['reference']}");
            flash('success', "État du véhicule ($type) enregistré.");
            header("Location: /location/public/index.php?url=reservations/view&id=$reservationId");
            exit;
        }

        require __DIR__ . "/../Views/etat-vehicule/add.php";
    }

    /**
     * GET — Display a single inspection report in full detail.
     *
     * The query joins etat_vehicule → reservations → vehicles → clients → users
     * to display all associated data without additional queries in the view.
     *
     * $fuelLabels and $propreteColors are helper maps for the view's display logic.
     */
    public function view() {
        $conn       = $this->conn;
        $id         = (int)($_GET['id'] ?? 0);
        if (!$id) { header("Location: /location/public/index.php?url=etat-vehicule"); exit; }

        $stmt = $conn->prepare("
            SELECT ev.*, r.reference, r.date_debut, r.date_fin_prevue,
                   v.numero, v.marque, v.modele,
                   c.nom AS client_nom, c.prenom AS client_prenom,
                   u.username AS created_by_name
            FROM etat_vehicule ev
            JOIN reservations r ON ev.reservation_id = r.id
            JOIN vehicles v ON ev.vehicle_id = v.id
            JOIN clients c ON r.client_id = c.id
            LEFT JOIN users u ON ev.created_by = u.id
            WHERE ev.id = ?
        ");
        $stmt->execute([$id]);
        $e = $stmt->fetch();
        if (!$e) { flash('danger', 'Fiche introuvable.'); header("Location: /location/public/index.php?url=etat-vehicule"); exit; }

        $fuelLabels      = ['0','1/8','1/4','3/8','1/2','5/8','3/4','7/8','Plein'];
        $propreteColors  = ['propre' => 'success', 'moyen' => 'warning', 'sale' => 'danger'];

        require __DIR__ . "/../Views/etat-vehicule/view.php";
    }
}
