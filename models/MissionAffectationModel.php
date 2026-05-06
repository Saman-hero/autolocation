<?php

class MissionAffectationModel {

    private $conn;
    private $table = "mission_affectations";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByMissionId($mission_id) {
        $sql = "SELECT ma.*, 
                CONCAT(c.prenom, ' ', c.nom) as chauffeur_nom,
                v.numero as vehicle_numero,
                v.marque as vehicle_marque
                FROM {$this->table} ma
                LEFT JOIN chauffeurs c ON ma.chauffeur_id = c.id
                LEFT JOIN vehicles v ON ma.vehicle_id = v.id
                WHERE ma.mission_id = :mission_id
                ORDER BY ma.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":mission_id" => $mission_id]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table}
        (mission_id, vehicle_id, chauffeur_id, action_type, commentaire, changed_by)
        VALUES
        (:mission_id, :vehicle_id, :chauffeur_id, :action_type, :commentaire, :changed_by)";

        return $this->conn->prepare($sql)->execute($data);
    }

    public function getCurrentAffectation($mission_id) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE mission_id = :mission_id 
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":mission_id" => $mission_id]);
        return $stmt->fetch();
    }
}