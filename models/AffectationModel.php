<?php

class AffectationModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function assign($data) {
        $sql = "INSERT INTO mission_affectations
        (mission_id, vehicle_id, chauffeur_id, chef_bord_id, action_type, commentaire, changed_by)
        VALUES (:mission_id, :vehicle_id, :chauffeur_id, :chef_bord_id, :action_type, :commentaire, :changed_by)";

        return $this->conn->prepare($sql)->execute($data);
    }

    public function getCurrent($mission_id) {
        $stmt = $this->conn->prepare("SELECT * FROM mission_affectations WHERE mission_id=? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$mission_id]);
        return $stmt->fetch();
    }

    public function history($mission_id) {
        $sql = "SELECT ma.*, 
                       v.numero AS vehicle_numero,
                       c.nom AS chauffeur_nom,
                       cb.nom AS chef_nom
                FROM mission_affectations ma
                LEFT JOIN vehicles v ON ma.vehicle_id = v.id
                LEFT JOIN chauffeurs c ON ma.chauffeur_id = c.id
                LEFT JOIN chauffeurs cb ON ma.chef_bord_id = cb.id
                WHERE ma.mission_id = ?
                ORDER BY ma.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$mission_id]);
        return $stmt->fetchAll();
    }
}