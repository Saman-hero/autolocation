<?php
class GPSModel {
    private $conn;
    public function __construct($db) { $this->conn = $db; }

    public function savePosition($vehicleId, $lat, $lng, $speed = null, $altitude = null, $heading = null) {
        $sql = "INSERT INTO gps_positions (vehicle_id, latitude, longitude, speed, altitude, heading)
                VALUES (?, ?, ?, ?, ?, ?)";
        return $this->conn->prepare($sql)->execute([$vehicleId, $lat, $lng, $speed, $altitude, $heading]);
    }

    public function getLastPosition($vehicleId) {
        $stmt = $this->conn->prepare("SELECT * FROM gps_positions WHERE vehicle_id = ? ORDER BY recorded_at DESC LIMIT 1");
        $stmt->execute([$vehicleId]);
        return $stmt->fetch();
    }

    public function getAllLastPositions() {
        return $this->conn->query("
            SELECT g.*, v.marque, v.modele, v.numero, v.statut
            FROM gps_positions g
            JOIN vehicles v ON v.id = g.vehicle_id
            WHERE g.id IN (SELECT MAX(id) FROM gps_positions GROUP BY vehicle_id)
        ")->fetchAll();
    }

    public function getPositions($vehicleId, $limit = 100) {
        $stmt = $this->conn->prepare("SELECT * FROM gps_positions WHERE vehicle_id = ? ORDER BY recorded_at DESC LIMIT ?");
        $stmt->execute([$vehicleId, $limit]);
        return $stmt->fetchAll();
    }
}
