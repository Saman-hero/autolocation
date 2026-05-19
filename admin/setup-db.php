<?php
require_once "../config/database.php";

if ($_SESSION['user_role'] !== 'admin') {
    flash('danger', 'Accès réservé aux administrateurs.');
    header("Location: /location/index.php"); exit;
}

$db   = new Database();
$conn = $db->getConnection();

$tables = [];
$errors = [];

// 1. audit_logs
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_name VARCHAR(100),
        action VARCHAR(50) NOT NULL,
        table_name VARCHAR(50),
        record_id INT,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $tables[] = ['name' => 'audit_logs', 'status' => 'OK', 'class' => 'success'];
} catch (Exception $e) {
    $errors[] = 'audit_logs: ' . $e->getMessage();
    $tables[] = ['name' => 'audit_logs', 'status' => 'ERREUR: ' . $e->getMessage(), 'class' => 'danger'];
}

// 2. password_resets
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $tables[] = ['name' => 'password_resets', 'status' => 'OK', 'class' => 'success'];
} catch (Exception $e) {
    $tables[] = ['name' => 'password_resets', 'status' => 'ERREUR: ' . $e->getMessage(), 'class' => 'danger'];
}

// 3. etat_vehicule
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS etat_vehicule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reservation_id INT NOT NULL,
        vehicle_id INT NOT NULL,
        type ENUM('depart','retour') NOT NULL,
        carburant TINYINT DEFAULT 4,
        km INT,
        proprete ENUM('propre','moyen','sale') DEFAULT 'propre',
        rayures TINYINT(1) DEFAULT 0,
        dommages TEXT,
        notes TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $tables[] = ['name' => 'etat_vehicule', 'status' => 'OK', 'class' => 'success'];
} catch (Exception $e) {
    $tables[] = ['name' => 'etat_vehicule', 'status' => 'ERREUR: ' . $e->getMessage(), 'class' => 'danger'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Configuration base de données — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container py-4" style="max-width:700px">
  <div class="page-header">
    <div>
      <h1 class="page-title">Configuration base de données</h1>
      <div class="text-muted small">Création des nouvelles tables requises par les fonctionnalités avancées</div>
    </div>
    <a href="/location/index.php" class="btn btn-outline-secondary">← Accueil</a>
  </div>

  <div class="card mb-4">
    <div class="card-header">Résultat de la migration</div>
    <div class="card-body p-0">
      <table class="table mb-0">
        <thead class="table-light">
          <tr><th>Table</th><th>Statut</th></tr>
        </thead>
        <tbody>
          <?php foreach ($tables as $t): ?>
          <tr>
            <td class="font-monospace fw-semibold"><?= htmlspecialchars($t['name']) ?></td>
            <td><span class="badge bg-<?= $t['class'] ?>"><?= htmlspecialchars($t['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (empty($errors)): ?>
  <div class="alert alert-success">
    <strong>✓ Migration réussie !</strong> Toutes les tables ont été créées (ou existent déjà).
    Vous pouvez maintenant utiliser toutes les fonctionnalités avancées.
  </div>
  <?php else: ?>
  <div class="alert alert-danger">
    <strong>Erreurs rencontrées :</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">Accès rapides aux nouvelles fonctionnalités</div>
    <div class="card-body d-flex flex-column gap-2">
      <a href="/location/admin/audit.php" class="btn btn-outline-primary">Journal d'audit des actions</a>
      <a href="/location/reservations/calendar.php" class="btn btn-outline-primary">Calendrier des réservations</a>
      <a href="/location/etat-vehicule/index.php" class="btn btn-outline-primary">États des véhicules</a>
      <a href="/location/forgot-password.php" class="btn btn-outline-secondary">Réinitialisation mot de passe</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
