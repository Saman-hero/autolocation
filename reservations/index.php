<?php
require_once "../config/database.php";
require_once "../models/ReservationModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ReservationModel($conn);

$filters = [
    'q'          => trim($_GET['q'] ?? ''),
    'statut'     => $_GET['statut'] ?? '',
    'client_id'  => (int)($_GET['client_id'] ?? 0),
    'vehicle_id' => (int)($_GET['vehicle_id'] ?? 0),
    'from'       => $_GET['from'] ?? '',
    'to'         => $_GET['to']   ?? '',
];

$reservations = $model->getAll(array_filter($filters));
$clients      = $conn->query("SELECT id, nom, prenom FROM clients ORDER BY nom")->fetchAll();
$vehicles     = $conn->query("SELECT id, numero FROM vehicles ORDER BY numero")->fetchAll();

$statuts = ['en attente','confirmée','en cours','terminée','annulée'];
$rBadge  = [
    'en attente' => 'bg-secondary',
    'confirmée'  => 'bg-primary',
    'en cours'   => 'badge-encours',
    'terminée'   => 'badge-terminee',
    'annulée'    => 'badge-annulee',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Réservations — Location</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">Réservations / Locations</h1>
    <a href="add.php" class="btn btn-success">+ Nouvelle location</a>
  </div>

  <!-- Filtres -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
          <input type="text" name="q" class="form-control" placeholder="Référence, client, véhicule…" value="<?= htmlspecialchars($filters['q']) ?>">
        </div>
        <div class="col-md-2">
          <select name="statut" class="form-select">
            <option value="">Tous statuts</option>
            <?php foreach ($statuts as $s): ?>
              <option value="<?= $s ?>" <?= $filters['statut'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="client_id" class="form-select">
            <option value="">Tous clients</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $filters['client_id'] == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1">
          <input type="date" name="from" class="form-control" value="<?= $filters['from'] ?>">
        </div>
        <div class="col-md-1">
          <input type="date" name="to" class="form-control" value="<?= $filters['to'] ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Filtrer</button>
          <a href="index.php" class="btn btn-outline-secondary ms-1">Réinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Tableau -->
  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($reservations)): ?>
        <div class="empty-state">
          <span class="empty-icon">📋</span>
          <p>Aucune réservation trouvée</p>
          <a href="add.php" class="btn btn-success btn-sm">+ Créer la première</a>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Référence</th>
              <th>Client</th>
              <th>Véhicule</th>
              <th>Début</th>
              <th>Fin prévue</th>
              <th>Jours</th>
              <th>Total</th>
              <th>Statut</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($reservations as $r): ?>
            <?php
              $retard = $r['statut'] === 'en cours'
                && $r['date_fin_prevue']
                && new DateTime($r['date_fin_prevue']) < new DateTime();
            ?>
            <tr class="<?= $retard ? 'table-danger' : '' ?>">
              <td class="fw-semibold"><?= htmlspecialchars($r['reference']) ?></td>
              <td>
                <?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?>
              </td>
              <td>
                <strong><?= htmlspecialchars($r['vehicle_numero']) ?></strong>
                <div class="text-muted small"><?= htmlspecialchars($r['marque'] . ' ' . $r['modele']) ?></div>
              </td>
              <td class="text-muted small"><?= date('d/m/Y', strtotime($r['date_debut'])) ?></td>
              <td class="text-muted small">
                <?= date('d/m/Y', strtotime($r['date_fin_prevue'])) ?>
                <?php if ($retard): ?><span class="badge bg-danger ms-1">Retard</span><?php endif; ?>
              </td>
              <td><?= $r['nb_jours'] ?: '—' ?></td>
              <td class="fw-semibold"><?= $r['montant_total'] ? number_format($r['montant_total'], 2) . ' MAD' : '—' ?></td>
              <td><span class="badge <?= $rBadge[$r['statut']] ?? 'bg-secondary' ?>"><?= ucfirst($r['statut']) ?></span></td>
              <td class="text-end">
                <a href="view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
                <?php if (in_array($r['statut'], ['en attente','confirmée'])): ?>
                  <a href="start.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Démarrer cette location ?')">Démarrer</a>
                <?php elseif ($r['statut'] === 'en cours'): ?>
                  <a href="finish.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Clôturer</a>
                <?php endif; ?>
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
