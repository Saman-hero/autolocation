<?php
class TrajetModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $sql = "INSERT INTO trajets 
        (mission_id, ville_depart, ville_arrivee, date_depart, date_arrivee, ordre)
        VALUES (:mission_id, :ville_depart, :ville_arrivee, :date_depart, :date_arrivee, :ordre)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function deleteByMission($mission_id) {
        $stmt = $this->conn->prepare("DELETE FROM trajets WHERE mission_id=?");
        $stmt->execute([$mission_id]);
    }
}