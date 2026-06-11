<?php
class PublicController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        require_once __DIR__ . '/../models/VehicleModel.php';
        require_once __DIR__ . '/../models/ReservationModel.php';
    }

    public function index() {
        $vehicleModel = new VehicleModel($this->conn);
        $vehicles = $vehicleModel->getAvailable();
        $categories = $this->conn->query("SELECT DISTINCT categorie FROM vehicles WHERE statut='disponible'")->fetchAll(PDO::FETCH_COLUMN);
        require __DIR__ . '/../Views/public/index.php';
    }

    public function calendar() {
        header('Content-Type: application/json');
        $vehicleId = (int)($_GET['vehicle_id'] ?? 0);
        $sql = "SELECT id AS title, DATE(date_debut) AS start, DATE(date_fin_prevue) AS end
                FROM reservations WHERE statut IN ('confirmée','en cours')";
        $params = [];
        if ($vehicleId) {
            $sql .= " AND vehicle_id = ?";
            $params[] = $vehicleId;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        foreach ($events as &$e) {
            // FullCalendar expects end date to be exclusive
            if ($e['end']) {
                $d = new DateTime($e['end']);
                $d->modify('+1 day');
                $e['end'] = $d->format('Y-m-d');
            }
            $e['title'] = 'Réservé';
            $e['backgroundColor'] = '#ef4444';
            $e['borderColor'] = '#dc2626';
            $e['display'] = 'background';
        }
        echo json_encode($events);
    }

    public function book() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /location/public/index.php?url=public");
            exit;
        }
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $dateDebut = $_POST['date_debut'] ?? '';
        $dateFin = $_POST['date_fin'] ?? '';
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');

        // Validate
        $errors = [];
        if (!$vehicleId) $errors[] = 'Véhicule requis';
        if (!$dateDebut) $errors[] = 'Date de début requise';
        if (!$dateFin) $errors[] = 'Date de fin requise';
        if (!$nom) $errors[] = 'Nom requis';
        if (!$email) $errors[] = 'Email requis';

        if (!empty($errors)) {
            $_SESSION['booking_errors'] = $errors;
            $_SESSION['booking_data'] = $_POST;
            header("Location: /location/public/index.php?url=public");
            exit;
        }

        // Get vehicle price
        $vehicleModel = new VehicleModel($this->conn);
        $vehicle = $vehicleModel->getById($vehicleId);
        if (!$vehicle || $vehicle['statut'] !== 'disponible') {
            $_SESSION['booking_errors'] = ['Véhicule non disponible'];
            header("Location: /location/public/index.php?url=public");
            exit;
        }

        // Calculate days
        $d1 = new DateTime($dateDebut);
        $d2 = new DateTime($dateFin);
        $nbJours = max(1, $d1->diff($d2)->days);

        // Create reservation
        $reservationModel = new ReservationModel($this->conn);
        $reference = $reservationModel->generateReference();
        $montantTotal = $vehicle['prix_jour'] * $nbJours;

        // Find or create client
        $stmt = $this->conn->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if ($client) {
            $clientId = $client['id'];
            // Update contact info
            $this->conn->prepare("UPDATE clients SET telephone = COALESCE(NULLIF(?, ''), telephone) WHERE id = ?")->execute([$telephone, $clientId]);
        } else {
            $this->conn->prepare("INSERT INTO clients (nom, prenom, email, telephone, statut, type_client, created_at)
                VALUES (?, '', ?, ?, 'actif', 'particulier', NOW())")->execute([$nom, $email, $telephone]);
            $clientId = $this->conn->lastInsertId();
        }

        $reservationId = $reservationModel->create([
            ':reference' => $reference,
            ':client_id' => $clientId,
            ':vehicle_id' => $vehicleId,
            ':statut' => 'confirmée',
            ':date_debut' => $dateDebut,
            ':date_fin_prevue' => $dateFin,
            ':lieu_depart' => 'Agence',
            ':lieu_retour' => 'Agence',
            ':prix_jour' => $vehicle['prix_jour'],
            ':nb_jours' => $nbJours,
            ':caution' => $vehicle['caution'],
            ':montant_total' => $montantTotal,
            ':commentaire' => "Réservation en ligne - $nom",
            ':created_by' => 1,
        ]);

        $_SESSION['booking_success'] = [
            'reference' => $reference,
            'vehicule' => $vehicle['marque'] . ' ' . $vehicle['modele'],
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'total' => $montantTotal,
            'nom' => $nom,
            'email' => $email,
        ];

        header("Location: /location/public/index.php?url=public/confirmation");
        exit;
    }

    public function confirmation() {
        $data = $_SESSION['booking_success'] ?? null;
        if (!$data) {
            header("Location: /location/public/index.php?url=public");
            exit;
        }
        unset($_SESSION['booking_success']);
        require __DIR__ . '/../Views/public/confirmation.php';
    }
}
