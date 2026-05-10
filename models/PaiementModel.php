<?php

class PaiementModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByReservation($reservationId) {
        $stmt = $this->conn->prepare("SELECT * FROM paiements WHERE reservation_id=? ORDER BY date_paiement");
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO paiements
            (reservation_id, montant, type_paiement, type, reference_transaction, date_paiement, notes)
        VALUES
            (:reservation_id, :montant, :type_paiement, :type, :reference_transaction, :date_paiement, :notes)";
        return $this->conn->prepare($sql)->execute($data);
    }

    public function delete($id) {
        return $this->conn->prepare("DELETE FROM paiements WHERE id=?")->execute([$id]);
    }
}
