<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Parc Véhicules — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    /* ── Page ─────────────────────────────────────────────── */
    body { background: #f4f6f9; }

    /* ── Filters bar ─────────────────────────────────────── */
    .filter-bar {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      padding: 20px 24px;
      margin-bottom: 24px;
    }

    .filter-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: #6b7280;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .filter-input {
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 14px;
      width: 100%;
      transition: border-color .2s, box-shadow .2s;
      background: #fafafa;
    }

    .filter-input:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59,130,246,.1);
      background: #fff;
    }

    .filter-input::placeholder {
      color: #9ca3af;
      font-size: 13px;
    }

    .filter-select {
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 14px;
      width: 100%;
      transition: border-color .2s, box-shadow .2s;
      background: #fafafa;
      cursor: pointer;
    }

    .filter-select:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59,130,246,.1);
      background: #fff;
    }

    .btn-search {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 10px 24px;
      font-size: 14px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: transform .15s, box-shadow .15s;
      cursor: pointer;
    }

    .btn-search:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(59,130,246,.3);
      color: #fff;
    }

    .btn-reset {
      background: #f3f4f6;
      color: #374151;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 10px 20px;
      font-size: 14px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background .2s;
      cursor: pointer;
      text-decoration: none;
    }

    .btn-reset:hover {
      background: #e5e7eb;
      color: #374151;
    }

    /* ── Result header ───────────────────────────────────── */
    .result-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .result-count {
      font-size: 14px;
      color: #374151;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      background: #fff;
      padding: 8px 16px;
      border-radius: 8px;
      border: 1px solid #e5e7eb;
    }

    .result-count i {
      color: #3b82f6;
    }

    /* ── Vehicle card ─────────────────────────────────────── */
    .vehicle-card {
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0,0,0,.06);
      border: 1px solid #eef0f4;
      transition: transform .25s, box-shadow .25s;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .vehicle-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 32px rgba(0,0,0,.12);
    }

    /* ── Image container ─────────────────────────────────── */
    .vehicle-img-wrapper {
      position: relative;
      height: 200px;
      overflow: hidden;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .vehicle-img-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .4s ease;
    }

    .vehicle-card:hover .vehicle-img-wrapper img {
      transform: scale(1.05);
    }

    .vehicle-placeholder {
      font-size: 80px;
      opacity: .4;
    }

    /* ── Status badge ────────────────────────────────────── */
    .status-badge {
      position: absolute;
      top: 12px;
      right: 12px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .04em;
      display: flex;
      align-items: center;
      gap: 5px;
      backdrop-filter: blur(8px);
    }

    .status-disponible {
      background: rgba(16, 185, 129, .9);
      color: #fff;
    }

    .status-loue {
      background: rgba(245, 158, 11, .9);
      color: #fff;
    }

    .status-maintenance {
      background: rgba(239, 68, 68, .9);
      color: #fff;
    }

    .status-indisponible {
      background: rgba(107, 114, 128, .9);
      color: #fff;
    }

    /* ── Card body ───────────────────────────────────────── */
    .vehicle-card-body {
      padding: 16px 18px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .vehicle-name {
      font-size: 18px;
      font-weight: 800;
      color: #111827;
      margin-bottom: 4px;
      line-height: 1.2;
    }

    .vehicle-meta {
      font-size: 13px;
      color: #6b7280;
      margin-bottom: 12px;
    }

    .vehicle-meta span {
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    /* ── Feature chips ───────────────────────────────────── */
    .vehicle-features {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 16px;
    }

    .feature-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #f3f4f6;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 5px 10px;
      font-size: 12px;
      font-weight: 600;
      color: #374151;
    }

    .feature-chip i {
      font-size: 12px;
      color: #6b7280;
    }

    /* ── Price section ───────────────────────────────────── */
    .vehicle-price {
      margin-top: auto;
      padding-top: 14px;
      border-top: 1px solid #f3f4f6;
      text-align: center;
    }

    .price-value {
      font-size: 28px;
      font-weight: 900;
      color: #111827;
      line-height: 1;
    }

    .price-currency {
      font-size: 16px;
      font-weight: 700;
      color: #3b82f6;
    }

    .price-period {
      font-size: 13px;
      color: #9ca3af;
      font-weight: 500;
    }

    /* ── Action buttons ──────────────────────────────────── */
    .vehicle-actions {
      display: flex;
      gap: 10px;
      margin-top: 14px;
    }

    .btn-view {
      flex: 1;
      background: #f3f4f6;
      color: #374151;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: background .2s, border-color .2s;
      text-decoration: none;
    }

    .btn-view:hover {
      background: #e5e7eb;
      border-color: #d1d5db;
      color: #374151;
    }

    .btn-rent {
      flex: 1;
      background: linear-gradient(135deg, #10b981, #059669);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: transform .15s, box-shadow .15s;
      text-decoration: none;
    }

    .btn-rent:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(16,185,129,.3);
      color: #fff;
    }

    .btn-rent.disabled {
      background: linear-gradient(135deg, #9ca3af, #6b7280);
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .btn-rent.disabled:hover {
      transform: none;
      box-shadow: none;
    }

    /* ── Empty state ─────────────────────────────────────── */
    .empty-premium {
      text-align: center;
      padding: 80px 20px;
      background: #fff;
      border-radius: 16px;
      border: 2px dashed #e5e7eb;
    }

    .empty-premium .empty-car {
      font-size: 80px;
      margin-bottom: 16px;
      opacity: .3;
    }

    .empty-premium h3 {
      color: #6b7280;
      font-weight: 700;
    }

    /* ── Grid layout ─────────────────────────────────────── */
    .vehicles-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    /* ── Responsive ──────────────────────────────────────── */
    @media (max-width: 1400px) {
      .vehicles-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 1200px) {
      .vehicles-grid { grid-template-columns: repeat(3, 1fr); }
      .vehicle-card-body { padding: 14px 16px; }
      .vehicle-name { font-size: 16px; }
      .price-value { font-size: 24px; }
    }

    @media (max-width: 992px) {
      .vehicles-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
      .vehicle-img-wrapper { height: 170px; }
      .vehicle-placeholder { font-size: 60px; }
      .vehicle-features { gap: 6px; }
      .feature-chip { padding: 4px 8px; font-size: 11px; }
      .vehicle-actions { flex-direction: column; gap: 8px; }
      .btn-view, .btn-rent { width: 100%; justify-content: center; }
    }

    @media (max-width: 768px) {
      .vehicles-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
      .vehicle-img-wrapper { height: 150px; }
      .vehicle-placeholder { font-size: 50px; }
      .vehicle-card-body { padding: 12px 14px; }
      .vehicle-name { font-size: 15px; margin-bottom: 2px; }
      .vehicle-meta { font-size: 12px; margin-bottom: 8px; }
      .vehicle-features { gap: 5px; margin-bottom: 10px; }
      .feature-chip { padding: 3px 7px; font-size: 10px; gap: 3px; }
      .feature-chip i { font-size: 10px; }
      .vehicle-price { padding-top: 10px; margin-top: auto; }
      .price-value { font-size: 22px; }
      .price-currency { font-size: 14px; }
      .price-period { font-size: 12px; }
      .vehicle-actions { margin-top: 10px; padding-top: 10px; }
      .btn-view, .btn-rent { padding: 8px 12px; font-size: 12px; }

      .filter-bar { padding: 16px; }
      .filter-bar .row { gap: 10px; }
      .filter-bar .col-md-3, .filter-bar .col-md-2 { flex: 0 0 100%; max-width: 100%; }
      .filter-label { font-size: 10px; }
      .filter-input, .filter-select { padding: 8px 12px; font-size: 13px; }
      .btn-search, .btn-reset { padding: 8px 16px; font-size: 13px; }

      .page-header { flex-direction: column; gap: 12px; align-items: flex-start; }
      .page-title { font-size: 1.2rem; }
    }

    @media (max-width: 576px) {
      .vehicles-grid { grid-template-columns: 1fr; gap: 14px; }
      .vehicle-card { flex-direction: column; }
      .vehicle-img-wrapper { height: 180px; }
      .vehicle-placeholder { font-size: 70px; }
      .vehicle-card-body { padding: 14px 16px; }
      .vehicle-name { font-size: 17px; }
      .vehicle-meta { font-size: 13px; }
      .vehicle-features { gap: 6px; }
      .feature-chip { padding: 5px 10px; font-size: 12px; }
      .vehicle-price { padding-top: 12px; }
      .price-value { font-size: 26px; }
      .vehicle-actions { flex-direction: row; }
      .btn-view, .btn-rent { padding: 10px; font-size: 13px; }

      .filter-bar .col-md-3, .filter-bar .col-md-2 { flex: 0 0 100%; max-width: 100%; }
      .filter-bar .d-flex { flex-direction: column; gap: 8px !important; }
      .filter-bar .d-flex .btn-search, .filter-bar .d-flex .btn-reset { width: 100%; justify-content: center; }

      .result-header { flex-direction: column; align-items: flex-start; gap: 8px; }
      .result-count { font-size: 13px; }
    }

    @media (max-width: 400px) {
      .container-fluid { padding-left: 12px !important; padding-right: 12px !important; }
      .vehicle-img-wrapper { height: 160px; }
      .vehicle-card-body { padding: 12px; }
      .vehicle-name { font-size: 15px; }
      .price-value { font-size: 22px; }
    }

    /* ── Animations ──────────────────────────────────────── */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .vehicle-card { animation: fadeInUp .4s ease both; }
    .vehicle-card:nth-child(1) { animation-delay: .05s; }
    .vehicle-card:nth-child(2) { animation-delay: .1s; }
    .vehicle-card:nth-child(3) { animation-delay: .15s; }
    .vehicle-card:nth-child(4) { animation-delay: .2s; }
    .vehicle-card:nth-child(5) { animation-delay: .25s; }
    .vehicle-card:nth-child(6) { animation-delay: .3s; }
    .vehicle-card:nth-child(7) { animation-delay: .35s; }
    .vehicle-card:nth-child(8) { animation-delay: .4s; }

    /* ── Touch improvements ──────────────────────────────── */
    @media (hover: none) and (pointer: coarse) {
      .vehicle-card:hover { transform: none; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
      .btn-view:active, .btn-rent:active { transform: scale(0.97); }
      .btn-search:active { transform: scale(0.97); }
    }

    /* ── Dark mode support ───────────────────────────────── */
    @media (prefers-color-scheme: dark) {
      body { background: #1a1a2e; }
      .filter-bar { background: #16213e; border-color: #0f3460; }
      .filter-input, .filter-select { background: #0f3460; border-color: #1a1a2e; color: #fff; }
      .filter-input::placeholder { color: #8899aa; }
      .vehicle-card { background: #16213e; border-color: #0f3460; }
      .vehicle-name { color: #e8e8e8; }
      .vehicle-meta { color: #8899aa; }
      .feature-chip { background: #0f3460; border-color: #1a1a2e; color: #ccc; }
      .vehicle-price { border-top-color: #0f3460; }
      .price-value { color: #e8e8e8; }
      .result-count { background: #16213e; border-color: #0f3460; color: #ccc; }
    }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>
<?php include __DIR__ . "/../../includes/flash.php"; ?>

<?php
/* ── Helpers ──────────────────────────────────────────────────────────────── */

/** Pick a car emoji based on category. */
function carEmoji(string $cat): string {
    $cat = strtolower($cat);
    if (str_contains($cat, 'suv') || str_contains($cat, '4x4')) return '🚙';
    if (str_contains($cat, 'luxe') || str_contains($cat, 'premium')) return '🏎️';
    if (str_contains($cat, 'utilitaire') || str_contains($cat, 'camion')) return '🚐';
    if (str_contains($cat, 'moto') || str_contains($cat, 'scoot')) return '🏍️';
    if (str_contains($cat, 'van') || str_contains($cat, 'minibus')) return '🚌';
    if (str_contains($cat, 'convertible') || str_contains($cat, 'cabriolet')) return '🚗';
    return '🚗';
}

/** CSS class for status badge. */
function statusBadgeClass(string $s): string {
    return match($s) {
        'disponible'   => 'status-disponible',
        'loué'         => 'status-loue',
        'maintenance'  => 'status-maintenance',
        'indisponible' => 'status-indisponible',
        default        => 'status-disponible',
    };
}

/** Label for status badge. */
function statusLabel(string $s): string {
    return match($s) {
        'disponible'   => '✓ DISPONIBLE',
        'loué'         => '🔑 LOUÉ',
        'maintenance'  => '🔧 MAINTENANCE',
        'indisponible' => '⛔ INDISPONIBLE',
        default        => ucfirst($s),
    };
}

/**
 * Get transmission type.
 * TODO: Add 'transmission' column to vehicles table in database.
 * For now returns a default value.
 */
function getTransmission(array $v): string {
    return $v['transmission'] ?? 'Manuelle';
}

/**
 * Get fuel type.
 * TODO: Add 'carburant' column to vehicles table in database.
 * For now returns a default value.
 */
function getFuelType(array $v): string {
    return $v['carburant'] ?? 'Essence';
}
?>

<div class="container-fluid px-4 py-4" style="max-width:1300px">

  <!-- Header -->
  <div class="page-header mb-4 flex-wrap gap-3">
    <div>
      <h1 class="page-title mb-0">Parc Véhicules</h1>
      <p class="text-muted small mb-0 mt-1">Gérez votre flotte et les tarifs de location</p>
    </div>
    <a href="/location/public/index.php?url=vehicles/add" class="btn btn-success px-4 flex-shrink-0">
      <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Ajouter un véhicule</span>
    </a>
  </div>

  <!-- Filters -->
  <div class="filter-bar">
    <form method="GET" action="/location/public/index.php">
      <input type="hidden" name="url" value="vehicles">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="filter-label">
            <i class="fas fa-car"></i> MARQUE
          </div>
          <input type="text" name="q" class="filter-input"
                 placeholder="Ex: Toyota, BMW..."
                 value="<?= htmlspecialchars($keyword ?? '') ?>">
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="filter-label">
            <i class="fas fa-tags"></i> MODÈLE
          </div>
          <input type="text" name="modele" class="filter-input"
                 placeholder="Ex: Clio, Serie 3..."
                 value="<?= htmlspecialchars($modele ?? '') ?>">
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="filter-label">
            <i class="fas fa-list-check"></i> STATUT
          </div>
          <select name="statut" class="filter-select">
            <option value="">Tous</option>
            <option value="disponible" <?= ($statut ?? '') === 'disponible' ? 'selected' : '' ?>>✅ Disponible</option>
            <option value="loué" <?= ($statut ?? '') === 'loué' ? 'selected' : '' ?>>🔑 Loué</option>
            <option value="maintenance" <?= ($statut ?? '') === 'maintenance' ? 'selected' : '' ?>>🔧 Maintenance</option>
            <option value="indisponible" <?= ($statut ?? '') === 'indisponible' ? 'selected' : '' ?>>⛔ Indisponible</option>
          </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 d-flex gap-2 flex-wrap">
          <button type="submit" class="btn-search flex-fill">
            <i class="fas fa-search"></i> Rechercher
          </button>
          <a href="/location/public/index.php?url=vehicles" class="btn-reset flex-fill">
            <i class="fas fa-rotate-right"></i> Réinitialiser
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Results header -->
  <div class="result-header">
    <div class="result-count">
      <i class="fas fa-car-side"></i>
      <?= count($vehicles) ?> véhicule<?= count($vehicles) > 1 ? 's' : '' ?> trouvé<?= count($vehicles) > 1 ? 's' : '' ?>
    </div>
  </div>

  <!-- Vehicle cards grid -->
  <?php if (empty($vehicles)): ?>
    <div class="empty-premium">
      <div class="empty-car">🚗</div>
      <h3>Aucun véhicule trouvé</h3>
      <p class="text-muted">Modifiez vos filtres ou ajoutez un nouveau véhicule.</p>
      <a href="/location/public/index.php?url=vehicles/add" class="btn btn-success mt-2">
        <i class="fas fa-plus"></i> Ajouter le premier véhicule
      </a>
    </div>
  <?php else: ?>
    <div class="vehicles-grid">
      <?php foreach ($vehicles as $v):
        $isDisponible = $v['statut'] === 'disponible';
        $transmission = getTransmission($v);
        $fuel = getFuelType($v);
      ?>
        <div class="vehicle-card">
          <!-- Image -->
          <div class="vehicle-img-wrapper">
            <span class="status-badge <?= statusBadgeClass($v['statut']) ?>">
              <?= statusLabel($v['statut']) ?>
            </span>
            <?php if (!empty($v['image'])): ?>
              <img src="/location/uploads/vehicles/<?= htmlspecialchars($v['image']) ?>"
                   alt="<?= htmlspecialchars($v['marque'] . ' ' . $v['modele']) ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
              <span class="vehicle-placeholder" style="display:none"><?= carEmoji($v['categorie'] ?? '') ?></span>
            <?php else: ?>
              <span class="vehicle-placeholder"><?= carEmoji($v['categorie'] ?? '') ?></span>
            <?php endif; ?>
          </div>

          <!-- Body -->
          <div class="vehicle-card-body">
            <!-- Name -->
            <h3 class="vehicle-name"><?= htmlspecialchars($v['marque'] . ' ' . $v['modele']) ?></h3>

            <!-- Meta -->
            <div class="vehicle-meta">
              <span><?= $v['annee'] ?? 'N/A' ?></span>
              <span>•</span>
              <span><?= htmlspecialchars($v['immatriculation'] ?: $v['numero']) ?></span>
            </div>

            <!-- Features -->
            <div class="vehicle-features">
              <?php if ($v['nb_places']): ?>
                <span class="feature-chip">
                  <i class="fas fa-users"></i> <?= $v['nb_places'] ?> places
                </span>
              <?php endif; ?>
              <span class="feature-chip">
                <i class="fas fa-cogs"></i> <?= $transmission ?>
              </span>
              <span class="feature-chip">
                <i class="fas fa-gas-pump"></i> <?= $fuel ?>
              </span>
            </div>

            <!-- Price -->
            <div class="vehicle-price">
              <span class="price-value"><?= number_format($v['prix_jour'], 0, ',', ' ') ?></span>
              <span class="price-currency">MAD</span>
              <span class="price-period">/ jour</span>
            </div>

            <!-- Actions -->
            <div class="vehicle-actions">
              <a href="/location/public/index.php?url=vehicles/edit&id=<?= $v['id'] ?>" class="btn-view">
                <i class="fas fa-eye"></i> Voir
              </a>
              <?php if ($isDisponible): ?>
                <a href="/location/public/index.php?url=reservations/add&vehicle_id=<?= $v['id'] ?>" class="btn-rent">
                  <i class="fas fa-key"></i> Louer
                </a>
              <?php else: ?>
                <span class="btn-rent disabled">
                  <i class="fas fa-ban"></i> Indisponible
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
