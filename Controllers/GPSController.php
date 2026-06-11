<?php
class GPSController {
    private $conn;
    private $model;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        require_once __DIR__ . '/../models/GPSModel.php';
        $this->model = new GPSModel($this->conn);
    }

    public function index() {
        $vehicles = $this->conn->query("SELECT id, marque, modele, numero FROM vehicles ORDER BY marque")->fetchAll();
        $positions = [];
        try {
            $positions = $this->model->getAllLastPositions();
        } catch (Exception $e) {
            // Table doesn't exist yet
        }
        require __DIR__ . '/../Views/gps/index.php';
    }

    public function update() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);
        if (!$vehicleId || !$lat || !$lng) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        $speed = isset($_POST['speed']) ? (float)$_POST['speed'] : null;
        $altitude = isset($_POST['altitude']) ? (float)$_POST['altitude'] : null;
        $heading = isset($_POST['heading']) ? (float)$_POST['heading'] : null;
        try {
            $this->model->savePosition($vehicleId, $lat, $lng, $speed, $altitude, $heading);
            echo json_encode(['success' => true, 'recorded_at' => date('Y-m-d H:i:s')]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'GPS table not set up. Run setup_features.php']);
        }
    }
}
