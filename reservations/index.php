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
  <title>Réservations — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <style>
    .skeleton { background: linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%); background-size:200%; animation: shimmer 1.4s infinite; border-radius:4px; height:18px; }
    @keyframes shimmer { from{background-position:200%}to{background-position:-200%} }
    #resTableBody tr { animation: rowFadeIn .25s ease both; }
    @keyframes rowFadeIn { from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)} }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">Réservations / Locations</h1>
    <div class="d-flex gap-2 flex-wrap">
      <a href="calendar.php" class="btn btn-outline-primary btn-sm">📅 Calendrier</a>
      <a href="/location/export/reservations-csv.php" class="btn btn-outline-secondary btn-sm">CSV</a>
      <a href="/location/export/reservations-pdf.php" class="btn btn-outline-secondary btn-sm" target="_blank">PDF</a>
      <a href="add.php" class="btn btn-success">+ Nouvelle location</a>
    </div>
  </div>

  <!-- Filtres -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <input type="text" id="searchQ" class="form-control" placeholder="Référence, client, véhicule…"
                 value="<?= htmlspecialchars($filters['q']) ?>">
        </div>
        <div class="col-md-2">
          <select id="filterStatut" class="form-select">
            <option value="">Tous statuts</option>
            <?php foreach ($statuts as $s): ?>
              <option value="<?= $s ?>" <?= $filters['statut'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select id="filterClient" class="form-select">
            <option value="">Tous clients</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $filters['client_id'] == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1">
          <input type="date" id="filterFrom" class="form-control" value="<?= $filters['from'] ?>">
        </div>
        <div class="col-md-1">
          <input type="date" id="filterTo" class="form-control" value="<?= $filters['to'] ?>">
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
      <span>Liste des réservations</span>
      <span class="badge bg-secondary" id="countBadge"><?= count($reservations) ?> résultat(s)</span>
    </div>
    <div class="card-body p-0">
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
          <tbody id="resTableBody">
          <?php foreach ($reservations as $i => $r):
            $retard = $r['statut'] === 'en cours' && $r['date_fin_prevue'] && new DateTime($r['date_fin_prevue']) < new DateTime();
          ?>
            <tr class="<?= $retard ? 'table-danger' : '' ?>" style="animation-delay:<?= $i * 35 ?>ms">
              <td class="fw-semibold"><?= htmlspecialchars($r['reference']) ?></td>
              <td><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></td>
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
                <a href="pdf.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Contrat">🖨</a>
                <a href="invoice.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Facture">📄</a>
                <?php if (in_array($r['statut'], ['en attente','confirmée'])): ?>
                  <a href="start.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Démarrer cette location ?')">Démarrer</a>
                <?php elseif ($r['statut'] === 'en cours'): ?>
                  <a href="finish.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Clôturer</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($reservations)): ?>
            <tr><td colspan="9" class="text-center py-4 text-muted">Aucune réservation trouvée</td></tr>
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
const rBadge = {'en attente':'bg-secondary','confirmée':'bg-primary','en cours':'badge-encours','terminée':'badge-terminee','annulée':'badge-annulee'};

function escHtml(s) {
  if (!s) return '—';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function fmtDate(d) {
  if (!d) return '—';
  const dt = new Date(d); if (isNaN(dt)) return d;
  return dt.toLocaleDateString('fr-FR',{day:'2-digit',month:'2-digit',year:'numeric'});
}

function buildRow(r, idx) {
  const badge = rBadge[r.statut] || 'bg-secondary';
  const retardBadge = r.retard ? '<span class="badge bg-danger ms-1">Retard</span>' : '';
  let actions = `<a href="/location/reservations/view.php?id=${r.id}" class="btn btn-sm btn-outline-primary">Voir</a>
    <a href="/location/reservations/pdf.php?id=${r.id}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Contrat">🖨</a>
    <a href="/location/reservations/invoice.php?id=${r.id}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Facture">📄</a>`;
  if (r.statut === 'en attente' || r.statut === 'confirmée')
    actions += `<a href="/location/reservations/start.php?id=${r.id}" class="btn btn-sm btn-success" onclick="return confirm('Démarrer ?')">Démarrer</a>`;
  else if (r.statut === 'en cours')
    actions += `<a href="/location/reservations/finish.php?id=${r.id}" class="btn btn-sm btn-warning">Clôturer</a>`;

  return `<tr class="${r.retard?'table-danger':''}" style="animation-delay:${idx*35}ms">
    <td class="fw-semibold">${escHtml(r.reference)}</td>
    <td>${escHtml(r.client_nom+' '+r.client_prenom)}</td>
    <td><strong>${escHtml(r.vehicle_numero)}</strong><div class="text-muted small">${escHtml(r.marque+' '+r.modele)}</div></td>
    <td class="text-muted small">${fmtDate(r.date_debut)}</td>
    <td class="text-muted small">${fmtDate(r.date_fin_prevue)}${retardBadge}</td>
    <td>${r.nb_jours||'—'}</td>
    <td class="fw-semibold">${r.montant_total?Number(r.montant_total).toLocaleString('fr-FR',{minimumFractionDigits:2})+' MAD':'—'}</td>
    <td><span class="badge ${badge}">${r.statut.charAt(0).toUpperCase()+r.statut.slice(1)}</span></td>
    <td class="text-end">${actions}</td>
  </tr>`;
}

function doSearch() {
  const q      = document.getElementById('searchQ').value.trim();
  const statut = document.getElementById('filterStatut').value;
  const client = document.getElementById('filterClient').value;
  const from   = document.getElementById('filterFrom').value;
  const to     = document.getElementById('filterTo').value;

  const params = new URLSearchParams();
  if (q)      params.append('q', q);
  if (statut) params.append('statut', statut);
  if (client) params.append('client_id', client);
  if (from)   params.append('from', from);
  if (to)     params.append('to', to);

  fetch('/location/api/search-reservations.php?' + params.toString())
    .then(r => r.json()).then(rows => {
      document.getElementById('countBadge').textContent = rows.length + ' résultat(s)';
      const tbody = document.getElementById('resTableBody');
      if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">Aucune réservation trouvée</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map((r,i) => buildRow(r,i)).join('');
    }).catch(err => console.error(err));
}

function resetSearch() {
  ['searchQ','filterFrom','filterTo'].forEach(id => document.getElementById(id).value = '');
  ['filterStatut','filterClient'].forEach(id => document.getElementById(id).value = '');
  doSearch();
}

document.getElementById('searchQ').addEventListener('input', function() {
  clearTimeout(searchTimer); searchTimer = setTimeout(doSearch, 300);
});
['filterStatut','filterClient','filterFrom','filterTo'].forEach(id => {
  document.getElementById(id).addEventListener('change', doSearch);
});
</script>
</body>
</html>
