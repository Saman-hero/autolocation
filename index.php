<?php
require_once "config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$s = [
    'v_total'      => (int)$conn->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
    'v_dispo'      => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='disponible'")->fetchColumn(),
    'v_loue'       => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='loué'")->fetchColumn(),
    'v_maint'      => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='maintenance'")->fetchColumn(),
    'c_total'      => (int)$conn->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'c_actif'      => (int)$conn->query("SELECT COUNT(*) FROM clients WHERE statut='actif'")->fetchColumn(),
    'r_total'      => (int)$conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
    'r_encours'    => (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE statut='en cours'")->fetchColumn(),
    'r_mois'       => (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())")->fetchColumn(),
    'ca_mois'      => (float)$conn->query("SELECT COALESCE(SUM(montant_total),0) FROM reservations WHERE statut='terminée' AND YEAR(date_retour_effectif)=YEAR(NOW()) AND MONTH(date_retour_effectif)=MONTH(NOW())")->fetchColumn(),
    'ca_mois_prec' => (float)$conn->query("SELECT COALESCE(SUM(montant_total),0) FROM reservations WHERE statut='terminée' AND YEAR(date_retour_effectif)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND MONTH(date_retour_effectif)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH))")->fetchColumn(),
    'sin_ouverts'  => (int)$conn->query("SELECT COUNT(*) FROM sinistres WHERE statut='ouvert'")->fetchColumn(),
];

$s['taux_utilisation'] = $s['v_total'] > 0 ? round(($s['v_loue'] / $s['v_total']) * 100, 1) : 0;
$s['ca_variation'] = $s['ca_mois_prec'] > 0
    ? round((($s['ca_mois'] - $s['ca_mois_prec']) / $s['ca_mois_prec']) * 100, 1)
    : ($s['ca_mois'] > 0 ? 100 : 0);

// Réservations récentes
$recent = $conn->query("
    SELECT r.id, r.reference, r.statut, r.date_debut, r.date_fin_prevue, r.montant_total,
           c.nom AS client_nom, c.prenom AS client_prenom,
           v.numero AS vehicle_numero, v.marque, v.modele
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    ORDER BY r.created_at DESC
    LIMIT 8
")->fetchAll();

// Retards
$retards = $conn->query("
    SELECT r.id, r.reference, c.nom, c.prenom, v.numero, r.date_fin_prevue
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE r.statut = 'en cours' AND r.date_fin_prevue < NOW()
")->fetchAll();

// Alertes maintenance
$alertVehicles = $conn->query("
    SELECT id, numero, marque, modele, kilometrage,
           intervalle_vidange, derniere_vidange_km,
           (kilometrage - derniere_vidange_km) AS km_depuis_vidange
    FROM vehicles
    WHERE derniere_vidange_km IS NOT NULL
      AND intervalle_vidange > 0
      AND (kilometrage - derniere_vidange_km) >= (intervalle_vidange * 0.85)
    ORDER BY (kilometrage - derniere_vidange_km) DESC
")->fetchAll();

// Top 3 véhicules
$top3 = $conn->query("
    SELECT v.id, v.numero, v.marque, v.modele, COUNT(r.id) AS nb_locations
    FROM vehicles v
    LEFT JOIN reservations r ON r.vehicle_id = v.id
    GROUP BY v.id, v.numero, v.marque, v.modele
    ORDER BY nb_locations DESC
    LIMIT 3
")->fetchAll();

// Revenue chart 12 mois
$currentYear = date('Y');
$mByMonth = $conn->query("
    SELECT MONTH(created_at) AS mois, COUNT(*) AS total,
           COALESCE(SUM(montant_total),0) AS ca
    FROM reservations
    WHERE YEAR(created_at) = $currentYear
    GROUP BY MONTH(created_at)
    ORDER BY mois
")->fetchAll();
$moisLabels   = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
$mByMonthData = array_fill(0, 12, 0);
$mByMonthCA   = array_fill(0, 12, 0);
foreach ($mByMonth as $row) {
    $mByMonthData[(int)$row['mois'] - 1] = (int)$row['total'];
    $mByMonthCA[(int)$row['mois'] - 1]   = (float)$row['ca'];
}

// Activité récente (audit ou réservations)
$recentActivity = [];
try {
    $recentActivity = $conn->query("
        SELECT user_name, action, table_name, description, created_at
        FROM audit_logs ORDER BY created_at DESC LIMIT 10
    ")->fetchAll();
} catch (Exception $e) { /* table may not exist */ }

// Vehicle utilization for chart
$vUtilization = $conn->query("
    SELECT v.numero, v.marque, v.modele, COUNT(r.id) AS nb
    FROM vehicles v LEFT JOIN reservations r ON r.vehicle_id = v.id
    GROUP BY v.id ORDER BY nb DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tableau de bord — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>

<?php include "includes/navbar.php"; ?>
<?php include "includes/flash.php"; ?>

<!-- Top loading bar animation -->
<div id="pageLoader" style="position:fixed;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--primary),var(--accent));z-index:9999;animation:pageLoad .6s ease forwards;"></div>

<div class="container-fluid px-4 py-4">

  <!-- ALERTES RETARD -->
  <?php foreach ($retards as $ret): ?>
  <div class="alert alert-danger d-flex align-items-center justify-content-between py-2 mb-2 animate-flash">
    <div>
      🔴 <strong>Retour en retard</strong> &mdash;
      <?= htmlspecialchars($ret['nom'] . ' ' . $ret['prenom']) ?> · <?= htmlspecialchars($ret['numero']) ?>
      &nbsp;·&nbsp; Prévu le <?= date('d/m/Y', strtotime($ret['date_fin_prevue'])) ?>
    </div>
    <a href="/location/reservations/finish.php?id=<?= $ret['id'] ?>" class="btn btn-sm btn-danger ms-3">Clôturer</a>
  </div>
  <?php endforeach; ?>

  <!-- ALERTES MAINTENANCE -->
  <?php foreach ($alertVehicles as $av):
    $ratio   = $av['intervalle_vidange'] > 0 ? $av['km_depuis_vidange'] / $av['intervalle_vidange'] : 0;
    $overdue = $ratio >= 1;
  ?>
  <div class="alert <?= $overdue ? 'alert-danger' : 'alert-warning' ?> d-flex align-items-center justify-content-between py-2 mb-2">
    <div>
      <strong><?= $overdue ? '🔴 Maintenance dépassée' : '🟡 Maintenance imminente' ?></strong> &mdash;
      <strong><?= htmlspecialchars($av['numero']) ?></strong>
      (<?= htmlspecialchars($av['marque'] . ' ' . $av['modele']) ?>)
      &nbsp;·&nbsp; <?= number_format($av['km_depuis_vidange']) ?> km depuis la dernière vidange
    </div>
    <a href="/location/maintenance/add.php?vehicle_id=<?= $av['id'] ?>"
       class="btn btn-sm <?= $overdue ? 'btn-danger' : 'btn-warning' ?> ms-3">+ Maintenance</a>
  </div>
  <?php endforeach; ?>

  <!-- STAT CARDS with count-up -->
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
      <div class="stat-card stat-blue">
        <div class="stat-number" data-count="<?= $s['v_total'] ?>">0</div>
        <div class="stat-label">Véhicules</div>
        <div class="stat-sub">
          <span><?= $s['v_dispo'] ?> disponibles</span>
          <span><?= $s['v_loue'] ?> loués</span>
          <span><?= $s['v_maint'] ?> maint.</span>
        </div>
        <div class="stat-bg-icon">🚗</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="stat-card stat-green">
        <div class="stat-number" data-count="<?= $s['c_total'] ?>">0</div>
        <div class="stat-label">Clients</div>
        <div class="stat-sub">
          <span><?= $s['c_actif'] ?> actifs</span>
        </div>
        <div class="stat-bg-icon">👤</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="stat-card stat-orange">
        <div class="stat-number" data-count="<?= $s['r_encours'] ?>">0</div>
        <div class="stat-label">Locations en cours</div>
        <div class="stat-sub">
          <span><?= $s['r_mois'] ?> ce mois</span>
          <span><?= $s['r_total'] ?> au total</span>
          <span><?= $s['taux_utilisation'] ?>% flotte</span>
        </div>
        <div class="stat-bg-icon">🔑</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="stat-card stat-slate">
        <div class="stat-number" data-count="<?= (int)$s['ca_mois'] ?>" data-suffix=" MAD">0</div>
        <div class="stat-label">CA ce mois</div>
        <div class="stat-sub">
          <?php
            $varClass = $s['ca_variation'] >= 0 ? 'text-success' : 'text-danger';
            $varSign  = $s['ca_variation'] >= 0 ? '+' : '';
          ?>
          <span class="<?= $varClass ?>"><?= $varSign ?><?= $s['ca_variation'] ?>% vs mois préc.</span>
          <?php if ($s['sin_ouverts'] > 0): ?>
            <span class="text-danger"><?= $s['sin_ouverts'] ?> sinistre(s)</span>
          <?php endif; ?>
        </div>
        <div class="stat-bg-icon">💰</div>
      </div>
    </div>
  </div>

  <!-- GRAPHIQUES -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-header">Statut flotte</div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <canvas id="chartVehicles" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>CA mensuel <?= $currentYear ?></span>
          <span class="badge bg-secondary" id="lastUpdateBadge">Chargement…</span>
        </div>
        <div class="card-body">
          <canvas id="chartByMonth" height="180"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-header">Actions rapides</div>
        <div class="card-body d-flex flex-column gap-2 justify-content-center">
          <a href="/location/reservations/add.php" class="btn btn-success py-3">+ Nouvelle location</a>
          <a href="/location/clients/add.php"       class="btn btn-primary py-2">+ Nouveau client</a>
          <a href="/location/vehicles/add.php"      class="btn btn-dark py-2">+ Ajouter véhicule</a>
          <a href="/location/reservations/calendar.php" class="btn btn-outline-primary py-2">📅 Calendrier</a>
          <a href="/location/paiements/add.php"     class="btn btn-outline-success py-2">+ Enregistrer paiement</a>
          <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          <a href="/location/admin/audit.php"       class="btn btn-outline-secondary py-2">🔍 Journal d'audit</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- TOP VEHICULES + ACTIVITÉ RÉCENTE + CHART UTILISATION -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-header">Top 3 véhicules</div>
        <div class="card-body">
          <?php foreach ($top3 as $i => $veh): ?>
          <div class="d-flex align-items-center justify-content-between py-2 <?= $i < 2 ? 'border-bottom' : '' ?>">
            <div>
              <span class="badge bg-primary me-2">#<?= $i + 1 ?></span>
              <strong><?= htmlspecialchars($veh['numero']) ?></strong>
              <span class="text-muted small ms-1"><?= htmlspecialchars($veh['marque'] . ' ' . $veh['modele']) ?></span>
            </div>
            <span class="badge bg-secondary"><?= $veh['nb_locations'] ?> loc.</span>
          </div>
          <?php endforeach; ?>
          <?php if (empty($top3)): ?>
            <div class="text-muted small text-center py-3">Aucune donnée</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-header">Utilisation des véhicules</div>
        <div class="card-body">
          <canvas id="chartUtilisation" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-header">Activité récente</div>
        <div class="card-body p-0">
          <?php if (!empty($recentActivity)): ?>
          <div class="list-group list-group-flush" style="font-size:.8rem">
            <?php foreach ($recentActivity as $act): ?>
            <div class="list-group-item list-group-item-action py-2">
              <div class="d-flex justify-content-between">
                <span class="fw-semibold"><?= htmlspecialchars($act['user_name'] ?? '—') ?></span>
                <span class="badge bg-secondary" style="font-size:.65rem"><?= htmlspecialchars($act['action']) ?></span>
              </div>
              <div class="text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%">
                <?= htmlspecialchars($act['description'] ?? '') ?>
              </div>
              <div class="text-muted" style="font-size:.72rem"><?= date('d/m/Y H:i', strtotime($act['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div class="empty-state py-3">
            <span class="empty-icon" style="font-size:2rem">📋</span>
            <p class="small">Aucune activité récente<br><a href="/location/admin/setup-db.php" class="small">Configurer l'audit</a></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- RÉCENTES LOCATIONS -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Locations récentes</span>
      <div class="d-flex gap-2">
        <a href="/location/reservations/calendar.php" class="btn btn-sm btn-outline-primary">📅 Calendrier</a>
        <a href="/location/reservations/index.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
      </div>
    </div>
    <div class="card-body p-0">
      <?php if (empty($recent)): ?>
        <div class="empty-state">
          <span class="empty-icon">📋</span>
          <p>Aucune location pour le moment</p>
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
              <th>Total</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php
          $rBadge = [
            'en attente' => 'bg-secondary',
            'confirmée'  => 'bg-primary',
            'en cours'   => 'badge-encours',
            'terminée'   => 'badge-terminee',
            'annulée'    => 'badge-annulee',
          ];
          foreach ($recent as $i => $r):
            $retard = $r['statut'] === 'en cours' && new DateTime($r['date_fin_prevue']) < new DateTime();
          ?>
            <tr class="<?= $retard ? 'table-warning' : '' ?>" style="animation-delay:<?= $i * 40 ?>ms">
              <td class="fw-semibold"><?= htmlspecialchars($r['reference']) ?></td>
              <td><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></td>
              <td>
                <strong><?= htmlspecialchars($r['vehicle_numero']) ?></strong>
                <span class="text-muted small"> <?= htmlspecialchars($r['marque'] . ' ' . $r['modele']) ?></span>
              </td>
              <td class="text-muted small"><?= date('d/m/Y', strtotime($r['date_debut'])) ?></td>
              <td class="text-muted small">
                <?= date('d/m/Y', strtotime($r['date_fin_prevue'])) ?>
                <?php if ($retard): ?><span class="badge bg-danger ms-1">Retard</span><?php endif; ?>
              </td>
              <td><?= $r['montant_total'] ? number_format($r['montant_total'], 2) . ' MAD' : '—' ?></td>
              <td>
                <span class="badge <?= $rBadge[$r['statut']] ?? 'bg-secondary' ?> <?= $r['statut'] === 'en cours' ? 'badge-pulse' : '' ?>">
                  <?= ucfirst($r['statut']) ?>
                </span>
              </td>
              <td><a href="/location/reservations/view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a></td>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// Hide page loader
setTimeout(() => { document.getElementById('pageLoader').style.opacity = '0'; }, 700);

// Count-up animation
function countUp(el, target, suffix) {
  const duration = 1000;
  const step = 16;
  const increment = target / (duration / step);
  let current = 0;
  const timer = setInterval(() => {
    current = Math.min(current + increment, target);
    el.textContent = Math.floor(current).toLocaleString('fr-FR') + (suffix || '');
    if (current >= target) clearInterval(timer);
  }, step);
}

document.querySelectorAll('[data-count]').forEach(el => {
  const target = parseInt(el.dataset.count) || 0;
  const suffix = el.dataset.suffix || '';
  if (target > 0) countUp(el, target, suffix);
  else el.textContent = '0' + suffix;
});

// Charts
new Chart(document.getElementById('chartVehicles'), {
  type: 'doughnut',
  data: {
    labels: ['Disponible', 'Loué', 'Maintenance', 'Indisponible'],
    datasets: [{
      data: [<?= $s['v_dispo'] ?>, <?= $s['v_loue'] ?>, <?= $s['v_maint'] ?>, <?= (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='indisponible'")->fetchColumn() ?>],
      backgroundColor: ['#10b981','#f59e0b','#ef4444','#6b7280'],
      borderWidth: 2, borderColor: '#fff'
    }]
  },
  options: { cutout: '65%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } } }, animation: { duration: 800 } }
});

new Chart(document.getElementById('chartByMonth'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($moisLabels) ?>,
    datasets: [
      {
        label: 'Locations',
        data: <?= json_encode($mByMonthData) ?>,
        backgroundColor: 'rgba(26,58,92,.75)',
        borderColor: '#1a3a5c',
        borderWidth: 1, borderRadius: 5,
        yAxisID: 'y'
      },
      {
        label: 'CA (MAD)',
        data: <?= json_encode($mByMonthCA) ?>,
        type: 'line',
        borderColor: '#f97316',
        backgroundColor: 'rgba(249,115,22,.1)',
        borderWidth: 2, tension: .4, fill: true,
        yAxisID: 'y1'
      }
    ]
  },
  options: {
    plugins: { legend: { labels: { font: { size: 11 } } } },
    scales: {
      y:  { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.04)' } },
      y1: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { callback: v => v.toLocaleString('fr-FR') + ' MAD', font: { size: 10 } } },
      x:  { grid: { display: false } }
    }
  }
});

new Chart(document.getElementById('chartUtilisation'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_map(fn($v) => $v['numero'], $vUtilization)) ?>,
    datasets: [{
      label: 'Locations',
      data: <?= json_encode(array_map(fn($v) => $v['nb'], $vUtilization)) ?>,
      backgroundColor: '#f97316',
      borderRadius: 4,
    }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.04)' } },
      y: { grid: { display: false } }
    }
  }
});

// Auto-refresh every 5 minutes
let refreshTimer = null;
function scheduleRefresh() {
  refreshTimer = setTimeout(() => {
    fetch('/location/api/dashboard-stats.php')
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const ts = new Date(data.ts * 1000);
          document.getElementById('lastUpdateBadge').textContent =
            'Mis à jour ' + ts.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
        }
        scheduleRefresh();
      }).catch(() => scheduleRefresh());
  }, 5 * 60 * 1000);
}
scheduleRefresh();
document.getElementById('lastUpdateBadge').textContent = 'En direct';
</script>
</body>
</html>
