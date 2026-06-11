<?php
/**
 * models/PaiementModel.php
 *
 * Data-access layer for the `paiements` table.
 * A reservation can have multiple payment records (partial payments, deposits,
 * refunds …). This model handles their storage and retrieval.
 *
 * Payment record fields:
 *   reservation_id       — FK to reservations.id.
 *   montant              — Amount paid (positive) or refunded (negative).
 *   type_paiement        — Payment method: 'espèces', 'carte', 'virement', 'chèque' …
 *   type                 — Payment nature: 'acompte', 'solde', 'caution', 'remboursement'.
 *   reference_transaction— Optional bank / card transaction reference.
 *   date_paiement        — Date the payment was received.
 *   notes                — Free-text notes for the payment record.
 */
class PaiementModel {

    /** @var PDO Active database connection injected via constructor. */
    private $conn;

    /**
     * @param PDO $db  Active PDO connection.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Return all payments for a given reservation, sorted chronologically.
     * Used on the reservation detail page to display payment history and
     * compute the outstanding balance (montant_total − total paid).
     *
     * @param int $reservationId  Reservation PK.
     * @return array  Payment rows ordered by date_paiement ASC.
     */
    public function getByReservation($reservationId) {
        $stmt = $this->conn->prepare("SELECT * FROM paiements WHERE reservation_id=? ORDER BY date_paiement");
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll();
    }

    /**
     * Insert a new payment record.
     *
     * @param array $data  Named placeholders for all columns.
     *                     Keys: :reservation_id, :montant, :type_paiement,
     *                           :type, :reference_transaction, :date_paiement, :notes
     * @return bool  True on success.
     */
    public function create($data) {
        $sql = "INSERT INTO paiements
            (reservation_id, montant, type_paiement, type, reference_transaction, date_paiement, notes)
        VALUES
            (:reservation_id, :montant, :type_paiement, :type, :reference_transaction, :date_paiement, :notes)";
        return $this->conn->prepare($sql)->execute($data);
    }

    /**
     * Delete a payment record by PK.
     * Note: there is currently no controller action for this — it is
     * available for future use (e.g. correcting an accidental payment entry).
     *
     * @param int $id  Payment PK.
     * @return bool
     */
    public function delete($id) {
        return $this->conn->prepare("DELETE FROM paiements WHERE id=?")->execute([$id]);
    }
}
