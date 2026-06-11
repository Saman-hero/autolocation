<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= __('geolocalisation') ?> — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <style>
    #map { height: 75vh; border-radius: 16px; z-index: 1; }
    .gps-sidebar { position: absolute; top: 20px; left: 20px; z-index: 1000; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.12); width: 280px; max-height: calc(75vh - 40px); overflow-y: auto; }
    .gps-sidebar .header { padding: 14px 16px; border-bottom: 1px solid #e5e7eb; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .gps-item { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background .15s; }
    .gps-item:hover { background: #f8fafc; }
    .gps-item .name { font-weight: 600; font-size: 14px; }
    .gps-item .status { font-size: 11px; display: inline-block; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
    .gps-item .status.online { background: #d1fae5; color: #065f46; }
    .gps-item .status.offline { background: #fee2e2; color: #991b1b; }
    .gps-item .time { font-size: 11px; color: #9ca3af; }
    .gps-container { position: relative; }
    .refresh-btn { position: absolute; top: 20px; right: 20px; z-index: 1000; background: #fff; border: none; border-radius: 10px; padding: 8px 16px; font-size: 13px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,.1); cursor: pointer; display: flex; align-items: center; gap: 6px; }
    .refresh-btn:hover { background: #f3f4f6; }
    @media (max-width: 768px) {
      .gps-sidebar { width: 220px; font-size: 12px; }
      .gps-sidebar .header { font-size: 12px; padding: 10px 12px; }
      .gps-item { padding: 8px 12px; }
    }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>

<div class="container-fluid px-4 py-4">
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="fas fa-satellite"></i> <?= __('geolocalisation') ?></h1>
      <p class="text-muted small mb-0 mt-1"><?= __('position_vehicule') ?></p>
    </div>
  </div>

  <div class="gps-container">
    <div id="map"></div>

    <div class="gps-sidebar" id="sidebar">
      <div class="header"><i class="fas fa-car"></i> <?= __('vehicules') ?> (<?= count($positions) ?>)</div>
      <div id="vehicleList">
        <?php foreach ($positions as $p): ?>
          <div class="gps-item" onclick="focusVehicle(<?= $p['latitude'] ?>, <?= $p['longitude'] ?>, '<?= htmlspecialchars($p['marque'] . ' ' . $p['modele']) ?>')">
            <div class="name"><?= htmlspecialchars($p['marque'] . ' ' . $p['modele']) ?> <span class="status online">● <?= __('disponible') ?></span></div>
            <div class="time"><?= __('maj') ?> : <?= date('H:i', strtotime($p['recorded_at'])) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($positions)): ?>
          <div class="gps-item" style="color:#9ca3af;text-align:center"><?= __('aucun_resultat') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <button class="refresh-btn" onclick="refreshPositions()"><i class="fas fa-rotate"></i> <?= __('rafraichir') ?></button>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('map').setView([31.7917, -7.0926], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap',
  maxZoom: 18
}).addTo(map);

const markers = {};

<?php foreach ($positions as $p): ?>
  (function() {
    const id = 'v<?= $p['vehicle_id'] ?>';
    const marker = L.marker([<?= $p['latitude'] ?>, <?= $p['longitude'] ?>])
      .addTo(map)
      .bindPopup('<strong><?= htmlspecialchars($p['marque'] . ' ' . $p['modele']) ?></strong><br><?= __('derniere_position') ?>: <?= date('H:i', strtotime($p['recorded_at'])) ?>');
    markers[id] = marker;
  })();
<?php endforeach; ?>

function focusVehicle(lat, lng, name) {
  map.setView([lat, lng], 15);
}

function refreshPositions() {
  location.reload();
}
</script>
</body>
</html>
