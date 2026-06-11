<?php
/**
 * Controllers/PaiementController.php
 *
 * Manages payment records linked to reservations.
 *
 * A reservation may have multiple payments (partial/full/deposit/refund).
 * This controller handles:
 *   index() — filterable list of all payments with running total.
 *   add()   — record a new payment against a reservation.
 *
 * Note: there is no edit or delete action in the current UI.
 * PaiementModel::delete() exists for future use if needed.
 */
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/PaiementModel.php";

class PaiementController {

    /** @var PDO */
    private $conn;

    /** @var PaiementModel */
    private $model;

    public function __construct() {
        $db = new Database();
        $this->conn  = $db->getConnection();
        $this->model = new PaiementModel($this->conn);
    }

    /**
     * GET — Display all payments with optional filters.
     *
     * Results join paiements → reservations → clients so each row shows the
     * reservation reference and client name without extra queries in the view.
     *
     * $totalMontant is the sum of all payments matching the current filters —
     * shown as a total at the bottom of the list.
     *
     * Query params:
     *   reservation_id — filter by specific reservation.
     *   type           — filter by payment nature ('acompte', 'solde' …).
     */
    public function index() {
        $filterRes  = (int)($_GET['reservation_id'] ?? 0);
        $filterType = $_GET['type'] ?? '';

        // Build the query dynamically to apply only the active filters.
        $sql = "SELECT p.*, r.reference AS res_ref, c.nom AS client_nom, c.prenom AS client_prenom
                FROM paiements p
                JOIN reservations r ON p.reservation_id = r.id
                JOIN clients c ON r.client_id = c.id
                WHERE 1=1";
        $params = [];
        if ($filterRes)  { $sql .= " AND p.reservation_id = ?"; $params[] = $filterRes; }
        if ($filterType) { $sql .= " AND p.type = ?";           $params[] = $filterType; }
        $sql .= " ORDER BY p.date_paiement DESC, p.id DESC"; // Newest payments first

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $paiements    = $stmt->fetchAll();

        // Compute the total amount of all displayed payments for the summary row.
        $totalMontant = array_sum(array_column($paiements, 'montant'));

        require __DIR__ . "/../Views/paiements/index.php";
    }

    /**
     * GET  — Show the add-payment form.
     * POST — Validate and save the payment, then redirect to the reservation view.
     *
     * The reservation dropdown shows each reservation's reference, total amount,
     * and how much has already been paid (subquery via COALESCE/SUM) so the
     * operator can see the outstanding balance before entering the payment.
     *
     * Cancelled reservations are excluded from the dropdown.
     *
     * $preResId pre-selects a reservation when the form is opened from the
     * reservation detail page via ?reservation_id=X.
     */
    public function add() {
        $preResId = (int)($_GET['reservation_id'] ?? 0); // Pre-select if opened from reservation view
        $errors   = [];

        // Load all non-cancelled reservations with their current payment totals.
        $reservations = $this->conn->query("
            SELECT r.id, r.reference, r.montant_total, c.nom, c.prenom,
                   COALESCE((SELECT SUM(p.montant) FROM paiements p WHERE p.reservation_id=r.id),0) AS total_paye
            FROM reservations r
            JOIN clients c ON r.client_id = c.id
            WHERE r.statut NOT IN ('annulée')
            ORDER BY r.created_at DESC
        ")->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resId   = (int)$_POST['reservation_id'];
            $montant = (float)$_POST['montant'];
            $date    = $_POST['date_paiement'];

            // Validation: reservation, positive amount, and date are all required.
            if (!$resId)       $errors[] = 'Sélectionner une réservation.';
            if ($montant <= 0) $errors[] = 'Montant invalide.';
            if (!$date)        $errors[] = 'Date requise.';

            if (!$errors) {
                $this->model->create([
                    ':reservation_id'        => $resId,
                    ':montant'               => $montant,
                    ':type_paiement'         => $_POST['type_paiement'],    // Payment method: espèces, carte …
                    ':type'                  => $_POST['type'],             // Nature: acompte, solde, caution …
                    ':reference_transaction' => trim($_POST['reference_transaction']) ?: null, // Bank ref (optional)
                    ':date_paiement'         => $date,
                    ':notes'                 => trim($_POST['notes']) ?: null,
                ]);
                flash('success', 'Paiement de ' . number_format($montant, 2) . ' MAD enregistré.');
                // Redirect back to the reservation detail page after saving.
                header("Location: /location/public/index.php?url=reservations/view&id=$resId");
                exit;
            }
        }

        require __DIR__ . "/../Views/paiements/add.php";
    }
}
