<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>États des véhicules — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>
<?php include __DIR__ . "/../../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">États des véhicules</h1>
    <span class="badge bg-secondary fs-6"><?= $total ?> fiche(s)</span>
  </div>

  <!-- Filtre -->
  <div class="card mb-3">
    <div class="card-body py-2">
      <div class="d-flex gap-2 align-items-center">
        <label class="text-muted small fw-semibold me-1">Type :</label>
        <a href="index.php" class="btn btn-sm <?= !$typeFilter ? 'btn-primary' : 'btn-outline-secondary' ?>">Tous</a>
        <a href="?type=depart"  class="btn btn-sm <?= $typeFilter==='depart'  ? 'btn-primary' : 'btn-outline-secondary' ?>">Départ</a>
        <a href="?type=retour"  class="btn btn-sm <?= $typeFilter==='retour'  ? 'btn-primary' : 'btn-outline-secondary' ?>">Retour</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($rows)): ?>
        <div class="empty-state">
          <span class="empty-icon">🚗</span>
          <p>Aucune fiche d'état véhicule</p>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Réservation</th>
              <th>Véhicule</th>
              <th>Client</th>
              <th>Carburant</th>
              <th>Km</th>
              <th>Propreté</th>
              <th>Rayures</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $e):
            $propreteColor = ['propre'=>'success','moyen'=>'warning','sale'=>'danger'];
          ?>
            <tr>
              <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($e['created_at'])) ?></td>
              <td>
                <span class="badge <?= $e['type'] === 'depart' ? 'bg-primary' : 'bg-success' ?>">
                  <?= ucfirst($e['type']) ?>
                </span>
              </td>
              <td>
                <a href="/location/public/index.php?url=reservations/view&id=<?= $e['res_id'] ?>" class="fw-semibold text-decoration-none">
                  <?= htmlspecialchars($e['reference']) ?>
                </a>
              </td>
              <td><?= htmlspecialchars($e['numero'] . ' ' . $e['marque'] . ' ' . $e['modele']) ?></td>
              <td><?= htmlspecialchars($e['client_nom'] . ' ' . $e['client_prenom']) ?></td>
              <td class="small"><?= $fuelLabels[$e['carburant']] ?? '—' ?></td>
              <td class="small"><?= $e['km'] ? number_format($e['km']) . ' km' : '—' ?></td>
              <td>
                <span class="badge bg-<?= $propreteColor[$e['proprete']] ?? 'secondary' ?>">
                  <?= ucfirst($e['proprete'] ?? '—') ?>
                </span>
              </td>
              <td>
                <?php if ($e['rayures']): ?>
                  <span class="badge bg-danger">Oui</span>
                <?php else: ?>
                  <span class="text-muted small">Non</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="view.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($pages > 1): ?>
      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
        <div class="text-muted small">Page <?= $page ?>/<?= $pages ?></div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&type=<?= $typeFilter ?>">‹</a></li><?php endif; ?>
            <?php for ($p = max(1,$page-2); $p <= min($pages,$page+2); $p++): ?>
              <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="?page=<?= $p ?>&type=<?= $typeFilter ?>"><?= $p ?></a></li>
            <?php endfor; ?>
            <?php if ($page < $pages): ?><li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&type=<?= $typeFilter ?>">›</a></li><?php endif; ?>
          </ul>
        </nav>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
