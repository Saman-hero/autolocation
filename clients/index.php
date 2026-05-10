<?php
require_once "../config/database.php";
require_once "../models/ClientModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ClientModel($conn);

$filters = [
    'q'           => trim($_GET['q'] ?? ''),
    'statut'      => $_GET['statut'] ?? '',
    'type_client' => $_GET['type_client'] ?? '',
];

$clients = $model->getAll(array_filter($filters));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Clients — Location</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">Clients</h1>
    <a href="add.php" class="btn btn-success">+ Nouveau client</a>
  </div>

  <!-- Filtres -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <input type="text" name="q" class="form-control" placeholder="Nom, CIN, téléphone, email…" value="<?= htmlspecialchars($filters['q']) ?>">
        </div>
        <div class="col-md-3">
          <select name="type_client" class="form-select">
            <option value="">Tous types</option>
            <option value="particulier" <?= $filters['type_client'] === 'particulier' ? 'selected' : '' ?>>Particulier</option>
            <option value="entreprise"  <?= $filters['type_client'] === 'entreprise'  ? 'selected' : '' ?>>Entreprise</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="statut" class="form-select">
            <option value="">Tous statuts</option>
            <option value="actif"        <?= $filters['statut'] === 'actif'        ? 'selected' : '' ?>>Actif</option>
            <option value="suspendu"     <?= $filters['statut'] === 'suspendu'     ? 'selected' : '' ?>>Suspendu</option>
            <option value="liste_noire"  <?= $filters['statut'] === 'liste_noire'  ? 'selected' : '' ?>>Liste noire</option>
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
      <?php if (empty($clients)): ?>
        <div class="empty-state">
          <span class="empty-icon">👤</span>
          <p>Aucun client trouvé</p>
          <a href="add.php" class="btn btn-success btn-sm">+ Ajouter le premier</a>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Nom</th>
              <th>CIN / Passeport</th>
              <th>Téléphone</th>
              <th>Email</th>
              <th>Type</th>
              <th>Permis</th>
              <th>Statut</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($clients as $c): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></strong>
                <?php if ($c['type_client'] === 'entreprise' && $c['entreprise']): ?>
                  <div class="text-muted small"><?= htmlspecialchars($c['entreprise']) ?></div>
                <?php endif; ?>
              </td>
              <td class="text-muted small"><?= htmlspecialchars($c['cin'] ?: '—') ?></td>
              <td><?= htmlspecialchars($c['telephone'] ?: '—') ?></td>
              <td class="text-muted small"><?= htmlspecialchars($c['email'] ?: '—') ?></td>
              <td>
                <span class="badge <?= $c['type_client'] === 'entreprise' ? 'bg-primary' : 'bg-secondary' ?>">
                  <?= $c['type_client'] === 'entreprise' ? 'Entreprise' : 'Particulier' ?>
                </span>
              </td>
              <td class="text-muted small">
                <?= htmlspecialchars($c['permis_numero'] ?: '—') ?>
                <?php if ($c['permis_expiration']): ?>
                  <?php $exp = new DateTime($c['permis_expiration']); $now = new DateTime(); ?>
                  <?php if ($exp < $now): ?>
                    <span class="badge bg-danger ms-1">Expiré</span>
                  <?php elseif ($exp < (new DateTime('+30 days'))): ?>
                    <span class="badge bg-warning text-dark ms-1">Expire bientôt</span>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $sBadge = ['actif' => 'badge-disponible', 'suspendu' => 'badge-conge', 'liste_noire' => 'badge-annulee'];
                  $sLabel = ['actif' => 'Actif', 'suspendu' => 'Suspendu', 'liste_noire' => 'Liste noire'];
                ?>
                <span class="badge <?= $sBadge[$c['statut']] ?? 'bg-secondary' ?>"><?= $sLabel[$c['statut']] ?? $c['statut'] ?></span>
              </td>
              <td class="text-end">
                <a href="view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
                <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
                <a href="delete.php?id=<?= $c['id'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Supprimer ce client ?')">Supprimer</a>
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
