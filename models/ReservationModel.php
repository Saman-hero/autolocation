<?php
/**
 * models/ReservationModel.php
 *
 * Data-access layer for the `reservations` table.
 * Joins with `clients` and `vehicles` to enrich query results so controllers
 * and views do not need to run separate look-ups for display data.
 *
 * Reservation lifecycle statuses:
 *   'en attente'  — created but not yet confirmed.
 *   'confirmée'   — confirmed, vehicle not yet handed over.
 *   'en cours'    — vehicle handed over, rental active.
 *   'terminée'    — vehicle returned, rental closed.
 *   'annulée'     — reservation cancelled before start.
 *
 * Reference format: LOC-YYYY-NNN (e.g. LOC-2026-007).
 * Generated automatically by generateReference() using a sequential counter
 * scoped to the current calendar year.
 */
class ReservationModel {

    /** @var PDO Active database connection injected via constructor. */
    private $conn;

    /**
     * @param PDO $db  Active PDO connection.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Return all reservations with enriched client + vehicle columns,
     * optionally narrowed by filters.
     *
     * Supported filter keys:
     *   'statut'     — exact status match.
     *   'client_id'  — filter by client PK.
     *   'vehicle_id' — filter by vehicle PK.
     *   'from'       — start date lower bound (DATE comparison).
     *   'to'         — start date upper bound (DATE comparison).
     *   'q'          — text search across reference, client name, vehicle number.
     *
     * Results are ordered newest first (created_at DESC).
     *
     * @param array $filters  Optional filter map.
     * @return array  Enriched reservation rows.
     */
    public function getAll($filters = []) {
        $sql = "
            SELECT r.*,
                   c.nom AS client_nom, c.prenom AS client_prenom,
                   v.numero AS vehicle_numero, v.marque, v.modele
            FROM reservations r
            JOIN clients  c ON r.client_id  = c.id
            JOIN vehicles v ON r.vehicle_id = v.id
            WHERE 1=1
        ";
        $params = [];

        // Status filter — matches one exact value.
        if (!empty($filters['statut']))     { $sql .= " AND r.statut = :statut";         $params[':statut']     = $filters['statut']; }

        // Client filter — show only reservations for a specific client.
        if (!empty($filters['client_id']))  { $sql .= " AND r.client_id = :client_id";   $params[':client_id']  = $filters['client_id']; }

        // Vehicle filter — show only reservations for a specific vehicle.
        if (!empty($filters['vehicle_id'])) { $sql .= " AND r.vehicle_id = :vehicle_id"; $params[':vehicle_id'] = $filters['vehicle_id']; }

        // Date range — compares against the rental start date (date_debut).
        if (!empty($filters['from']))       { $sql .= " AND DATE(r.date_debut) >= :from"; $params[':from']      = $filters['from']; }
        if (!empty($filters['to']))         { $sql .= " AND DATE(r.date_debut) <= :to";   $params[':to']        = $filters['to']; }

        // Keyword search — partial match across the most common search fields.
        if (!empty($filters['q'])) {
            $sql .= " AND (r.reference LIKE :q OR c.nom LIKE :q OR c.prenom LIKE :q OR v.numero LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $sql .= " ORDER BY r.created_at DESC"; // Newest reservation first
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single reservation enriched with full client and vehicle details.
     *
     * The extra columns (client_tel, client_cin, permis_numero, client_email,
     * vehicle categorie, couleur) are needed by the reservation view page,
     * contract PDF, and invoice PDF.
     *
     * @param int $id  Reservation PK.
     * @return array|false  Enriched row or false if not found.
     */
    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT r.*,
                   c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_tel,
                   c.cin AS client_cin, c.permis_numero, c.email AS client_email,
                   v.numero AS vehicle_numero, v.marque, v.modele, v.categorie, v.couleur
            FROM reservations r
            JOIN clients  c ON r.client_id  = c.id
            JOIN vehicles v ON r.vehicle_id = v.id
            WHERE r.id=?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Insert a new reservation and return its new primary key.
     *
     * Returning the new ID allows the controller to immediately redirect
     * to the reservation detail page after creation.
     *
     * @param array $data  Named placeholders for all insertable columns.
     * @return int  New reservation primary key.
     */
    public function create($data) {
        $sql = "INSERT INTO reservations
            (reference, client_id, vehicle_id, statut, date_debut, date_fin_prevue,
             lieu_depart, lieu_retour, prix_jour, nb_jours, caution, montant_total, commentaire, created_by)
        VALUES
            (:reference, :client_id, :vehicle_id, :statut, :date_debut, :date_fin_prevue,
             :lieu_depart, :lieu_retour, :prix_jour, :nb_jours, :caution, :montant_total, :commentaire, :created_by)";
        $this->conn->prepare($sql)->execute($data);
        return $this->conn->lastInsertId(); // Return the auto-incremented PK
    }

    /**
     * Update the editable fields of a pending or confirmed reservation.
     * Immutable fields (reference, client_id, vehicle_id, created_by) are excluded.
     *
     * @param array $data  Named placeholders including :id.
     * @return bool  True on success.
     */
    public function update($data) {
        $sql = "UPDATE reservations SET
            statut          = :statut,
            date_debut      = :date_debut,
            date_fin_prevue = :date_fin_prevue,
            lieu_depart     = :lieu_depart,
            lieu_retour     = :lieu_retour,
            prix_jour       = :prix_jour,
            nb_jours        = :nb_jours,
            caution         = :caution,
            montant_total   = :montant_total,
            commentaire     = :commentaire
        WHERE id = :id";
        return $this->conn->prepare($sql)->execute($data);
    }

    /**
     * Permanently delete a reservation record.
     * The controller blocks deletion of active ('en cours') reservations.
     *
     * @param int $id  Reservation PK.
     * @return bool
     */
    public function delete($id) {
        return $this->conn->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
    }

    /**
     * Compute the next sequential reference for the current year.
     *
     * Looks for the highest existing LOC-YYYY-NNN reference this year,
     * extracts its numeric suffix, and increments by 1.
     * Falls back to 001 if no reservation exists yet this year.
     *
     * Format examples: LOC-2026-001, LOC-2026-042, LOC-2026-100.
     *
     * @return string  Next available reference string.
     */
    private function nextReference(): string {
        $year = date('Y');
        // Find the most recently inserted reference for this year.
        $last = $this->conn->query("
            SELECT reference FROM reservations
            WHERE reference LIKE 'LOC-$year-%'
            ORDER BY id DESC LIMIT 1
        ")->fetchColumn();

        $num = 1; // Default to 001 if no previous reference exists
        if ($last) {
            $parts = explode('-', $last); // e.g. ['LOC', '2026', '007']
            $num   = (int)end($parts) + 1; // Increment the numeric suffix
        }
        return sprintf('LOC-%s-%03d', $year, $num); // Zero-padded to 3 digits
    }

    /**
     * Public accessor for the reference generator.
     * Called by ReservationController::add() to pre-fill the reference field.
     *
     * @return string  e.g. 'LOC-2026-008'
     */
    public function generateReference(): string {
        return $this->nextReference();
    }
}
