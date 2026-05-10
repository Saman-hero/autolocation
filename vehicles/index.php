<?php
require_once "../config/database.php";
require_once "../models/VehicleModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new VehicleModel($conn);

$keyword   = trim($_GET['q']        ?? '');
$categorie = $_GET['categorie']     ?? '';
$statut    = $_GET['statut']        ?? '';

$vehicles = $model->search($keyword ?: null, $categorie ?: null, $statut ?: null);

$categories = ['économique','berline','SUV','premium','utilitaire'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Véhicules — Location</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <div>
      <h1 class="page-title">Parc Véhicules</h1>
    </div>
    <a href="add.php" class="btn btn-success">+ Ajouter véhicule</a>
  </div>

  <!-- Filtres -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <input type="text" name="q" class="form-control" placeholder="Rechercher (numéro, marque, immat…)" value="<?= htmlspecialchars($keyword) ?>">
        </div>
        <div class="col-md-3">
          <select name="categorie" class="form-select">
            <option value="">Toutes catégories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>" <?= $categorie === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select name="statut" class="form-select">
            <option value="">Tous statuts</option>
            <option value="disponible"   <?= $statut === 'disponible'   ? 'selected' : '' ?>>Disponible</option>
            <option value="loué"         <?= $statut === 'loué'         ? 'selected' : '' ?>>Loué</option>
            <option value="maintenance"  <?= $statut === 'maintenance'  ? 'selected' : '' ?>>Maintenance</option>
            <option value="indisponible" <?= $statut === 'indisponible' ? 'selected' : '' ?>>Indisponible</option>
          </select>
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
      <?php if (empty($vehicles)): ?>
        <div class="empty-state">
          <span class="empty-icon">🚗</span>
          <p>Aucun véhicule trouvé</p>
          <a href="add.php" class="btn btn-success btn-sm">+ Ajouter le premier</a>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Numéro</th>
              <th>Immatriculation</th>
              <th>Véhicule</th>
              <th>Catégorie</th>
              <th>Kilométrage</th>
              <th>Prix/jour</th>
              <th>Caution</th>
              <th>Statut</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($vehicles as $v): ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($v['numero']) ?></td>
              <td class="text-muted small"><?= htmlspecialchars($v['immatriculation'] ?: '—') ?></td>
              <td>
                <strong><?= htmlspecialchars($v['marque'] . ' ' . $v['modele']) ?></strong>
                <div class="text-muted small"><?= $v['annee'] ?> · <?= htmlspecialchars($v['couleur'] ?: '—') ?> · <?= $v['nb_places'] ?> places</div>
              </td>
              <td><span class="badge bg-secondary"><?= htmlspecialchars($v['categorie']) ?></span></td>
              <td><?= number_format($v['kilometrage']) ?> km</td>
              <td class="fw-semibold text-primary"><?= number_format($v['prix_jour'], 2) ?> MAD</td>
              <td><?= number_format($v['caution'], 2) ?> MAD</td>
              <td>
                <?php
                  $badgeMap = [
                    'disponible'   => 'badge-disponible',
                    'loué'         => 'badge-mission',
                    'maintenance'  => 'badge-maintenance',
                    'indisponible' => 'badge-annulee',
                  ];
                  $badge = $badgeMap[$v['statut']] ?? 'bg-secondary';
                ?>
                <span class="badge <?= $badge ?>"><?= ucfirst($v['statut']) ?></span>
              </td>
              <td class="text-end">
                <a href="edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                <a href="delete.php?id=<?= $v['id'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Supprimer ce véhicule ?')">Supprimer</a>
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
