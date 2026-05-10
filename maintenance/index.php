<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$filterVehicle = (int)($_GET['vehicle_id'] ?? 0);
$filterStatut  = $_GET['statut'] ?? '';

$where  = [];
$params = [];

if ($filterVehicle) {
    $where[]  = "m.vehicle_id = ?";
    $params[] = $filterVehicle;
}
if ($filterStatut !== '') {
    $where[]  = "m.statut = ?";
    $params[] = $filterStatut;
}

$sql = "
    SELECT m.*, v.numero, v.marque, v.modele
    FROM maintenance m
    JOIN vehicles v ON m.vehicle_id = v.id
" . ($where ? " WHERE " . implode(" AND ", $where) : "") . "
    ORDER BY m.date_maintenance DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$vehicles = $conn->query("SELECT id, numero, marque, modele FROM vehicles ORDER BY numero")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Maintenance — Gestion Transport</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>

<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">🔧 Gestion des maintenances</h1>
    <a href="add.php" class="btn btn-primary">+ Nouvelle maintenance</a>
  </div>

  <!-- FILTRES -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label small text-muted">Véhicule</label>
          <select name="vehicle_id" class="form-select form-select-sm">
            <option value="">Tous les véhicules</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= $v['id'] ?>" <?= $v['id'] == $filterVehicle ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['numero'] . ' — ' . $v['marque'] . ' ' . $v['modele']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small text-muted">Statut</label>
          <select name="statut" class="form-select form-select-sm">
            <option value="">Tous les statuts</option>
            <option value="planifiée"  <?= $filterStatut === 'planifiée'  ? 'selected' : '' ?>>Planifiée</option>
            <option value="en cours"   <?= $filterStatut === 'en cours'   ? 'selected' : '' ?>>En cours</option>
            <option value="terminée"   <?= $filterStatut === 'terminée'   ? 'selected' : '' ?>>Terminée</option>
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-outline-primary btn-sm">Filtrer</button>
          <a href="index.php" class="btn btn-outline-secondary btn-sm ms-1">Réinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <!-- TABLE -->
  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($records)): ?>
        <div class="empty-state">
          <span class="empty-icon">🔧</span>
          <p>Aucune maintenance enregistrée</p>
          <a href="add.php" class="btn btn-primary btn-sm">+ Créer la première</a>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Véhicule</th>
              <th>Type</th>
              <th>Date</th>
              <th>Kilométrage</th>
              <th>Coût (MAD)</th>
              <th>Technicien</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($r['numero']) ?></strong>
                <div class="text-muted small"><?= htmlspecialchars($r['marque'] . ' ' . $r['modele']) ?></div>
              </td>
              <td><?= htmlspecialchars($r['type_maintenance']) ?></td>
              <td><?= date('d/m/Y', strtotime($r['date_maintenance'])) ?></td>
              <td><?= $r['kilometrage_intervention'] ? number_format($r['kilometrage_intervention']) . ' km' : '—' ?></td>
              <td><?= $r['cout'] ? number_format($r['cout'], 2) : '—' ?></td>
              <td><?= htmlspecialchars($r['technicien'] ?: '—') ?></td>
              <td>
                <?php
                  $badges = ['planifiée' => 'bg-warning text-dark', 'en cours' => 'bg-primary', 'terminée' => 'bg-success'];
                  $badge  = $badges[$r['statut']] ?? 'bg-secondary';
                ?>
                <span class="badge <?= $badge ?>"><?= htmlspecialchars($r['statut']) ?></span>
              </td>
              <td class="text-end">
                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                <a href="delete.php?id=<?= $r['id'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Supprimer cette maintenance ?')">Supprimer</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
