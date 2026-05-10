<?php
require_once "config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$s = [
    'v_total'      => $conn->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
    'v_dispo'      => $conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='disponible'")->fetchColumn(),
    'v_loue'       => $conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='loué'")->fetchColumn(),
    'v_maint'      => $conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='maintenance'")->fetchColumn(),
    'c_total'      => $conn->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'c_actif'      => $conn->query("SELECT COUNT(*) FROM clients WHERE statut='actif'")->fetchColumn(),
    'r_total'      => $conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
    'r_encours'    => $conn->query("SELECT COUNT(*) FROM reservations WHERE statut='en cours'")->fetchColumn(),
    'r_mois'       => $conn->query("SELECT COUNT(*) FROM reservations WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())")->fetchColumn(),
    'ca_mois'      => $conn->query("SELECT COALESCE(SUM(montant_total),0) FROM reservations WHERE statut='terminée' AND YEAR(date_retour_effectif)=YEAR(NOW()) AND MONTH(date_retour_effectif)=MONTH(NOW())")->fetchColumn(),
    'sin_ouverts'  => $conn->query("SELECT COUNT(*) FROM sinistres WHERE statut='ouvert'")->fetchColumn(),
];

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

// Réservations en retard
$retards = $conn->query("
    SELECT r.id, r.reference, c.nom, c.prenom, v.numero, r.date_fin_prevue
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE r.statut = 'en cours' AND r.date_fin_prevue < NOW()
")->fetchAll();

// Alertes maintenance vidange
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

// Graphique — réservations par mois (année courante)
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tableau de bord — Gestion Location</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>

<?php include "includes/navbar.php"; ?>
<?php include "includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <!-- ALERTES RETARD -->
  <?php foreach ($retards as $ret): ?>
  <div class="alert alert-danger d-flex align-items-center justify-content-between py-2 mb-2">
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

  <!-- STAT CARDS -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card stat-blue">
        <div class="stat-number"><?= $s['v_total'] ?></div>
        <div class="stat-label">Véhicules</div>
        <div class="stat-sub">
          <span><?= $s['v_dispo'] ?> disponibles</span>
          <span><?= $s['v_loue'] ?> loués</span>
          <span><?= $s['v_maint'] ?> maintenance</span>
        </div>
        <div class="stat-bg-icon">🚗</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card stat-green">
        <div class="stat-number"><?= $s['c_total'] ?></div>
        <div class="stat-label">Clients</div>
        <div class="stat-sub">
          <span><?= $s['c_actif'] ?> actifs</span>
        </div>
        <div class="stat-bg-icon">👤</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card stat-orange">
        <div class="stat-number"><?= $s['r_encours'] ?></div>
        <div class="stat-label">Locations en cours</div>
        <div class="stat-sub">
          <span><?= $s['r_mois'] ?> ce mois</span>
          <span><?= $s['r_total'] ?> au total</span>
        </div>
        <div class="stat-bg-icon">🔑</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card stat-slate">
        <div class="stat-number"><?= number_format($s['ca_mois'], 0) ?></div>
        <div class="stat-label">CA ce mois (MAD)</div>
        <div class="stat-sub">
          <?php if ($s['sin_ouverts'] > 0): ?>
            <span class="text-danger"><?= $s['sin_ouverts'] ?> sinistre(s) ouvert(s)</span>
          <?php else: ?>
            <span>Aucun sinistre ouvert</span>
          <?php endif; ?>
        </div>
        <div class="stat-bg-icon">💰</div>
      </div>
    </div>
  </div>

  <!-- GRAPHIQUES + ACTIONS -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-header">Statut de la flotte</div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <canvas id="chartVehicles" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-header">Locations par mois — <?= $currentYear ?></div>
        <div class="card-body">
          <canvas id="chartByMonth" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-header">Actions rapides</div>
        <div class="card-body d-flex flex-column gap-2 justify-content-center">
          <a href="/location/reservations/add.php" class="btn btn-success py-3">+ Nouvelle location</a>
          <a href="/location/clients/add.php"       class="btn btn-primary py-3">+ Nouveau client</a>
          <a href="/location/vehicles/add.php"      class="btn btn-dark py-3">+ Ajouter véhicule</a>
          <a href="/location/paiements/add.php"     class="btn btn-outline-success py-2">+ Enregistrer paiement</a>
          <a href="/location/sinistres/add.php"     class="btn btn-outline-danger py-2">+ Déclarer sinistre</a>
        </div>
      </div>
    </div>
  </div>

  <!-- RÉCENTES LOCATIONS -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Locations récentes</span>
      <a href="/location/reservations/index.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
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
          foreach ($recent as $r):
            $retard = $r['statut'] === 'en cours' && new DateTime($r['date_fin_prevue']) < new DateTime();
          ?>
            <tr class="<?= $retard ? 'table-warning' : '' ?>">
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
              <td><span class="badge <?= $rBadge[$r['statut']] ?? 'bg-secondary' ?>"><?= ucfirst($r['statut']) ?></span></td>
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
new Chart(document.getElementById('chartVehicles'), {
  type: 'doughnut',
  data: {
    labels: ['Disponible', 'Loué', 'Maintenance', 'Indisponible'],
    datasets: [{
      data: [<?= $s['v_dispo'] ?>, <?= $s['v_loue'] ?>, <?= $s['v_maint'] ?>, <?= (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='indisponible'")->fetchColumn() ?>],
      backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#6b7280'],
      borderWidth: 2, borderColor: '#fff'
    }]
  },
  options: { cutout: '65%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } } }, animation: { duration: 600 } }
});

new Chart(document.getElementById('chartByMonth'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($moisLabels) ?>,
    datasets: [{
      label: 'Locations <?= $currentYear ?>',
      data: <?= json_encode($mByMonthData) ?>,
      backgroundColor: 'rgba(27,94,53,.75)',
      borderColor: '#1b5e35',
      borderWidth: 1, borderRadius: 5
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.05)' } },
      x: { grid: { display: false } }
    }
  }
});
</script>
</body>
</html>
