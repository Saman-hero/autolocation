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
  <title>Clients — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <style>
    .skeleton { background: linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%); background-size: 200%; animation: shimmer 1.4s infinite; border-radius: 4px; height: 18px; }
    @keyframes shimmer { from{background-position:200%}to{background-position:-200%} }
    #clientsTableBody tr { animation: rowFadeIn .25s ease both; }
    @keyframes rowFadeIn { from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)} }
    .search-spinner { display:none; }
    .searching .search-spinner { display:inline-block; }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">Clients</h1>
    <div class="d-flex gap-2">
      <a href="/location/export/clients-csv.php" class="btn btn-outline-secondary btn-sm">CSV</a>
      <a href="/location/export/clients-pdf.php" class="btn btn-outline-secondary btn-sm" target="_blank">PDF</a>
      <a href="add.php" class="btn btn-success">+ Nouveau client</a>
    </div>
  </div>

  <!-- Filtres avec recherche live -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <div class="input-group">
            <input type="text" id="searchQ" class="form-control" placeholder="Nom, CIN, téléphone, email…"
                   value="<?= htmlspecialchars($filters['q']) ?>">
            <span class="input-group-text bg-transparent">
              <span class="spinner-border spinner-border-sm search-spinner" id="searchSpinner"></span>
            </span>
          </div>
        </div>
        <div class="col-md-3">
          <select id="filterType" class="form-select">
            <option value="">Tous types</option>
            <option value="particulier" <?= $filters['type_client'] === 'particulier' ? 'selected' : '' ?>>Particulier</option>
            <option value="entreprise"  <?= $filters['type_client'] === 'entreprise'  ? 'selected' : '' ?>>Entreprise</option>
          </select>
        </div>
        <div class="col-md-3">
          <select id="filterStatut" class="form-select">
            <option value="">Tous statuts</option>
            <option value="actif"       <?= $filters['statut'] === 'actif'       ? 'selected' : '' ?>>Actif</option>
            <option value="suspendu"    <?= $filters['statut'] === 'suspendu'    ? 'selected' : '' ?>>Suspendu</option>
            <option value="liste_noire" <?= $filters['statut'] === 'liste_noire' ? 'selected' : '' ?>>Liste noire</option>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-secondary" onclick="resetSearch()">Réinitialiser</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tableau -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Liste des clients</span>
      <span class="badge bg-secondary" id="countBadge"><?= count($clients) ?> client(s)</span>
    </div>
    <div class="card-body p-0" id="tableWrapper">
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
          <tbody id="clientsTableBody">
          <?php foreach ($clients as $i => $c):
            $sBadge = ['actif' => 'badge-disponible', 'suspendu' => 'bg-warning text-dark', 'liste_noire' => 'badge-annulee'];
            $sLabel = ['actif' => 'Actif', 'suspendu' => 'Suspendu', 'liste_noire' => 'Liste noire'];
          ?>
            <tr style="animation-delay:<?= $i * 40 ?>ms">
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
          <?php if (empty($clients)): ?>
            <tr id="emptyRow"><td colspan="8" class="text-center py-4 text-muted">Aucun client trouvé</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let searchTimer = null;

function escHtml(s) {
  if (!s) return '—';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function buildRow(c, idx) {
  const sBadge = {actif:'badge-disponible', suspendu:'bg-warning text-dark', liste_noire:'badge-annulee'};
  const sLabel = {actif:'Actif', suspendu:'Suspendu', liste_noire:'Liste noire'};
  const typeBadge = c.type_client === 'entreprise' ? 'bg-primary' : 'bg-secondary';
  const typeLabel = c.type_client === 'entreprise' ? 'Entreprise' : 'Particulier';

  let permisHtml = escHtml(c.permis_numero);
  if (c.permis_expired)  permisHtml += ' <span class="badge bg-danger ms-1">Expiré</span>';
  if (c.permis_expiring) permisHtml += ' <span class="badge bg-warning text-dark ms-1">Expire bientôt</span>';

  return `<tr style="animation-delay:${idx * 40}ms">
    <td><strong>${escHtml(c.nom + ' ' + c.prenom)}</strong>${c.type_client==='entreprise'&&c.entreprise?'<div class="text-muted small">'+escHtml(c.entreprise)+'</div>':''}</td>
    <td class="text-muted small">${escHtml(c.cin)}</td>
    <td>${escHtml(c.telephone)}</td>
    <td class="text-muted small">${escHtml(c.email)}</td>
    <td><span class="badge ${typeBadge}">${typeLabel}</span></td>
    <td class="text-muted small">${permisHtml}</td>
    <td><span class="badge ${sBadge[c.statut]||'bg-secondary'}">${sLabel[c.statut]||c.statut}</span></td>
    <td class="text-end">
      <a href="/location/clients/view.php?id=${c.id}" class="btn btn-sm btn-outline-primary">Voir</a>
      <a href="/location/clients/edit.php?id=${c.id}" class="btn btn-sm btn-outline-secondary">Modifier</a>
      <a href="/location/clients/delete.php?id=${c.id}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce client ?')">Supprimer</a>
    </td>
  </tr>`;
}

function doSearch() {
  const q      = document.getElementById('searchQ').value.trim();
  const type   = document.getElementById('filterType').value;
  const statut = document.getElementById('filterStatut').value;

  document.getElementById('searchSpinner').parentElement.parentElement.classList.add('searching');

  const params = new URLSearchParams();
  if (q)      params.append('q', q);
  if (type)   params.append('type_client', type);
  if (statut) params.append('statut', statut);

  fetch('/location/api/search-clients.php?' + params.toString())
    .then(r => r.json())
    .then(clients => {
      document.getElementById('searchSpinner').parentElement.parentElement.classList.remove('searching');
      const tbody = document.getElementById('clientsTableBody');
      document.getElementById('countBadge').textContent = clients.length + ' client(s)';

      if (clients.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Aucun client trouvé</td></tr>';
        return;
      }
      tbody.innerHTML = clients.map((c, i) => buildRow(c, i)).join('');
    })
    .catch(() => {
      document.getElementById('searchSpinner').parentElement.parentElement.classList.remove('searching');
    });
}

function resetSearch() {
  document.getElementById('searchQ').value     = '';
  document.getElementById('filterType').value  = '';
  document.getElementById('filterStatut').value = '';
  doSearch();
}

// Debounced input
document.getElementById('searchQ').addEventListener('input', function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(doSearch, 300);
});
document.getElementById('filterType').addEventListener('change', doSearch);
document.getElementById('filterStatut').addEventListener('change', doSearch);
</script>
</body>
</html>
