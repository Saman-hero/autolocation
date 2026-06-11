<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Clients — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>
<?php include __DIR__ . "/../../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">Clients</h1>
    <a href="/location/public/index.php?url=clients/add" class="btn btn-success">+ Ajouter client</a>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <input type="text" name="q" class="form-control" placeholder="Nom, prénom, CIN, email…" value="<?= htmlspecialchars($filters['q']) ?>">
        </div>
        <div class="col-md-3">
          <select name="statut" class="form-select">
            <option value="">Tous statuts</option>
            <option value="actif"    <?= ($filters['statut'] ?? '') === 'actif'    ? 'selected' : '' ?>>Actif</option>
            <option value="inactif"  <?= ($filters['statut'] ?? '') === 'inactif'  ? 'selected' : '' ?>>Inactif</option>
            <option value="bloqué"   <?= ($filters['statut'] ?? '') === 'bloqué'   ? 'selected' : '' ?>>Bloqué</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="type" class="form-select">
            <option value="">Tous types</option>
            <option value="particulier"  <?= ($filters['type'] ?? '') === 'particulier'  ? 'selected' : '' ?>>Particulier</option>
            <option value="entreprise"   <?= ($filters['type'] ?? '') === 'entreprise'   ? 'selected' : '' ?>>Entreprise</option>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Filtrer</button>
          <a href="/location/public/index.php?url=clients" class="btn btn-outline-secondary ms-1">Réinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Liste des clients</span>
      <span class="badge bg-secondary"><?= count($clients) ?> résultat(s)</span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($clients)): ?>
        <div class="empty-state">
          <span class="empty-icon">👤</span>
          <p>Aucun client trouvé</p>
          <a href="/location/public/index.php?url=clients/add" class="btn btn-success btn-sm">+ Ajouter le premier</a>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Nom</th><th>CIN</th><th>Téléphone</th>
              <th>Email</th><th>Type</th><th>Permis expire</th>
              <th>Statut</th><th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($clients as $c):
            $permisExpire = $c['permis_expiration'] ? new DateTime($c['permis_expiration']) : null;
            $permisExpired = $permisExpire && $permisExpire < new DateTime();
          ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></strong>
                <?php if ($c['entreprise']): ?>
                  <div class="text-muted small"><?= htmlspecialchars($c['entreprise']) ?></div>
                <?php endif; ?>
              </td>
              <td class="text-muted small"><?= htmlspecialchars($c['cin'] ?: '—') ?></td>
              <td class="text-muted small"><?= htmlspecialchars($c['telephone'] ?: '—') ?></td>
              <td class="text-muted small"><?= htmlspecialchars($c['email'] ?: '—') ?></td>
              <td><span class="badge bg-secondary"><?= ucfirst($c['type_client'] ?? '—') ?></span></td>
              <td class="small <?= $permisExpired ? 'text-danger fw-bold' : 'text-muted' ?>">
                <?= $permisExpire ? $permisExpire->format('d/m/Y') : '—' ?>
                <?php if ($permisExpired): ?><span class="badge bg-danger ms-1">Expiré</span><?php endif; ?>
              </td>
              <td>
                <?php $badge = ['actif'=>'badge-disponible','inactif'=>'bg-secondary','bloqué'=>'badge-annulee'][$c['statut']] ?? 'bg-secondary'; ?>
                <span class="badge <?= $badge ?>"><?= ucfirst($c['statut']) ?></span>
              </td>
              <td class="text-end">
                <a href="/location/public/index.php?url=clients/view&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
                <a href="/location/public/index.php?url=clients/edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
                <a href="/location/public/index.php?url=clients/delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger"
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
