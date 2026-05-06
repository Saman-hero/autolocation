<?php
class MissionTeamModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function add($mission_id, $vehicle_id, $chauffeur_id, $role) {
        $sql = "INSERT INTO mission_team(mission_id, vehicle_id, chauffeur_id, role)
                VALUES(?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$mission_id, $vehicle_id, $chauffeur_id, $role]);
    }

    public function deleteByMission($mission_id) {
        $stmt = $this->conn->prepare("DELETE FROM mission_team WHERE mission_id=?");
        $stmt->execute([$mission_id]);
    }
}