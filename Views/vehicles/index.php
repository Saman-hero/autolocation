<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Parc Véhicules — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <style>
    /* ── Page ─────────────────────────────────────────────── */
    body { background: #f4f6f9; }

    /* ── Filters bar ─────────────────────────────────────── */
    .filter-bar {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      padding: 18px 22px;
      margin-bottom: 24px;
    }

    /* ── Vehicle card ─────────────────────────────────────── */
    .vcard {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 16px rgba(0,0,0,.07);
      margin-bottom: 20px;
      display: flex;
      overflow: hidden;
      transition: box-shadow .2s, transform .2s;
      border: 1px solid #eef0f4;
      min-height: 190px;
    }
    .vcard:hover {
      box-shadow: 0 8px 32px rgba(27,94,53,.13);
      transform: translateY(-2px);
    }

    /* ── Image panel ──────────────────────────────────────── */
    .vcard-img {
      width: 230px;
      min-width: 230px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #f0f4f0 0%, #e8f0e9 100%);
      position: relative;
      overflow: hidden;
    }
    .vcard-img::after {
      content: '';
      position: absolute;
      bottom: 0; left: 0; right: 0;
      height: 4px;
      background: linear-gradient(90deg, #1b5e35, #2e7d52);
    }
    .vcard-img img {
      width: 200px;
      height: 130px;
      object-fit: contain;
      filter: drop-shadow(0 6px 16px rgba(0,0,0,.18));
    }
    .vcard-img .car-placeholder {
      font-size: 90px;
      line-height: 1;
      filter: drop-shadow(0 4px 12px rgba(0,0,0,.15));
      user-select: none;
    }
    .vcard-img .statut-ribbon {
      position: absolute;
      top: 12px; left: -28px;
      background: #1b5e35;
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      padding: 4px 36px;
      transform: rotate(-45deg);
    }
    .statut-ribbon.loue    { background: #d97706; }
    .statut-ribbon.maint   { background: #b91c1c; }
    .statut-ribbon.indispo { background: #6b7280; }

    /* ── Info panel ───────────────────────────────────────── */
    .vcard-info {
      flex: 1;
      padding: 20px 24px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .vcard-category {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: #5a7a66;
      background: #e8f4ed;
      border: 1px solid #c3dccb;
      border-radius: 20px;
      padding: 3px 10px;
      margin-bottom: 6px;
      width: fit-content;
    }
    .vcard-name {
      font-size: 22px;
      font-weight: 800;
      color: #1a2e22;
      letter-spacing: .01em;
      line-height: 1.15;
      margin-bottom: 2px;
    }
    .vcard-sub {
      font-size: 12px;
      color: #8a9ba8;
      margin-bottom: 14px;
    }

    /* ── Specs chips ─────────────────────────────────────── */
    .vcard-specs {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 14px;
    }
    .spec-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #f5f7fa;
      border: 1px solid #e4e8ed;
      border-radius: 8px;
      padding: 4px 10px;
      font-size: 12px;
      font-weight: 600;
      color: #3d5a47;
    }
    .spec-chip .spec-icon { font-size: 14px; }

    /* ── Includes ─────────────────────────────────────────── */
    .vcard-includes {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }
    .incl-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: #374151;
    }
    .incl-check {
      width: 18px; height: 18px;
      background: #1b5e35;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .incl-check svg { width: 10px; height: 10px; stroke: #fff; fill: none; }

    /* ── Actions strip ───────────────────────────────────── */
    .vcard-actions {
      display: flex;
      gap: 8px;
      margin-top: 14px;
      padding-top: 14px;
      border-top: 1px solid #f0f2f5;
      align-items: center;
    }
    .btn-details {
      background: none; border: none;
      font-size: 12px; color: #1b5e35;
      font-weight: 600; padding: 0;
      text-decoration: none;
      display: flex; align-items: center; gap: 4px;
    }
    .btn-details:hover { color: #134a27; text-decoration: underline; }

    /* ── Price panel ──────────────────────────────────────── */
    .vcard-price {
      width: 190px;
      min-width: 190px;
      background: #fafbfc;
      border-left: 1px solid #f0f2f5;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 18px;
      text-align: center;
      gap: 8px;
    }
    .price-label {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: #9ca3af;
    }
    .price-amount {
      font-size: 26px;
      font-weight: 900;
      color: #1a2e22;
      line-height: 1.1;
    }
    .price-amount span { font-size: 14px; font-weight: 600; color: #6b7280; }
    .price-total {
      font-size: 11px;
      color: #9ca3af;
    }
    .price-caution {
      font-size: 11px;
      color: #6b7280;
      background: #f3f4f6;
      border-radius: 6px;
      padding: 3px 8px;
    }
    .btn-select {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 10px 22px;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: .03em;
      text-transform: uppercase;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: opacity .15s, transform .15s;
      box-shadow: 0 4px 12px rgba(217,119,6,.3);
      width: 100%;
      text-align: center;
    }
    .btn-select:hover { opacity: .9; transform: translateY(-1px); color: #fff; }
    .btn-edit-sm {
      font-size: 11px; padding: 4px 10px;
      border-radius: 6px;
    }

    /* ── Empty state ─────────────────────────────────────── */
    .empty-premium {
      text-align: center;
      padding: 80px 20px;
    }
    .empty-premium .empty-car { font-size: 80px; margin-bottom: 16px; opacity: .3; }
    .empty-premium h3 { color: #6b7280; font-weight: 700; }

    /* ── Count badge ─────────────────────────────────────── */
    .result-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
    }
    .result-count {
      font-size: 13px;
      color: #6b7280;
      font-weight: 600;
    }

    /* ── Responsive ─────────────────────────────────────── */
    @media (max-width: 768px) {
      .vcard { flex-direction: column; }
      .vcard-img { width: 100%; min-width: unset; height: 150px; }
      .vcard-price { width: 100%; min-width: unset; border-left: none; border-top: 1px solid #f0f2f5; flex-direction: row; flex-wrap: wrap; justify-content: space-between; }
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

/** CSS class for status ribbon. */
function ribbonClass(string $s): string {
    return match($s) {
        'loué'         => 'loue',
        'maintenance'  => 'maint',
        'indisponible' => 'indispo',
        default        => '',
    };
}

/** Label for status ribbon. */
function ribbonLabel(string $s): string {
    return match($s) {
        'disponible'   => 'Disponible',
        'loué'         => 'Loué',
        'maintenance'  => 'En maintenance',
        'indisponible' => 'Indisponible',
        default        => ucfirst($s),
    };
}
?>

<div class="container-fluid px-4 py-4" style="max-width:1100px">

  <!-- Header -->
  <div class="page-header mb-4">
    <div>
      <h1 class="page-title mb-0">Parc Véhicules</h1>
      <p class="text-muted small mb-0 mt-1">Gérez votre flotte et les tarifs de location</p>
    </div>
    <a href="/location/public/index.php?url=vehicles/add" class="btn btn-success px-4">
      + Ajouter un véhicule
    </a>
  </div>

  <!-- Filters -->
  <div class="filter-bar">
    <form method="GET" action="/location/public/index.php" class="row g-2 align-items-end">
      <input type="hidden" name="url" value="vehicles">
      <div class="col-md-4">
        <label class="form-label fw-semibold small text-muted mb-1">Rechercher</label>
        <input type="text" name="q" class="form-control"
               placeholder="Numéro, marque, immatriculation…"
               value="<?= htmlspecialchars($keyword) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold small text-muted mb-1">Catégorie</label>
        <select name="categorie" class="form-select">
          <option value="">Toutes catégories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat ?>" <?= $categorie === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold small text-muted mb-1">Disponibilité</label>
        <select name="statut" class="form-select">
          <option value="">Tous statuts</option>
          <option value="disponible"   <?= $statut === 'disponible'   ? 'selected' : '' ?>>✅ Disponible</option>
          <option value="loué"         <?= $statut === 'loué'         ? 'selected' : '' ?>>🔑 Loué</option>
          <option value="maintenance"  <?= $statut === 'maintenance'  ? 'selected' : '' ?>>🔧 Maintenance</option>
          <option value="indisponible" <?= $statut === 'indisponible' ? 'selected' : '' ?>>⛔ Indisponible</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary flex-fill">Filtrer</button>
        <a href="/location/public/index.php?url=vehicles" class="btn btn-outline-secondary">✕</a>
      </div>
    </form>
  </div>

  <!-- Results header -->
  <div class="result-header">
    <span class="result-count"><?= count($vehicles) ?> véhicule<?= count($vehicles) > 1 ? 's' : '' ?> trouvé<?= count($vehicles) > 1 ? 's' : '' ?></span>
    <div class="d-flex gap-2 align-items-center">
      <?php
        $dispo = count(array_filter($vehicles, fn($v) => $v['statut'] === 'disponible'));
        $loues = count(array_filter($vehicles, fn($v) => $v['statut'] === 'loué'));
      ?>
      <?php if ($dispo): ?>
        <span class="badge" style="background:#1b5e35;font-size:11px">✅ <?= $dispo ?> disponible<?= $dispo>1?'s':'' ?></span>
      <?php endif; ?>
      <?php if ($loues): ?>
        <span class="badge bg-warning text-dark" style="font-size:11px">🔑 <?= $loues ?> loué<?= $loues>1?'s':'' ?></span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Vehicle cards -->
  <?php if (empty($vehicles)): ?>
    <div class="empty-premium">
      <div class="empty-car">🚗</div>
      <h3>Aucun véhicule trouvé</h3>
      <p class="text-muted">Modifiez vos filtres ou ajoutez un nouveau véhicule.</p>
      <a href="/location/public/index.php?url=vehicles/add" class="btn btn-success mt-2">+ Ajouter le premier véhicule</a>
    </div>
  <?php else: ?>

  <?php foreach ($vehicles as $v):
    $rClass = ribbonClass($v['statut']);
    $isDisponible = $v['statut'] === 'disponible';
    $nbJoursTotal = $v['caution'] > 0 ? round($v['caution'] / max($v['prix_jour'], 1)) : 0;
  ?>
  <div class="vcard">

    <!-- Image -->
    <div class="vcard-img">
      <span class="statut-ribbon <?= $rClass ?>"><?= ribbonLabel($v['statut']) ?></span>
      <span class="car-placeholder"><?= carEmoji($v['categorie']) ?></span>
    </div>

    <!-- Info -->
    <div class="vcard-info">
      <div>
        <!-- Category tag -->
        <div class="vcard-category">
          <span>OU SIMILAIRE</span>
          <span style="color:#1b5e35">◆</span>
          <?= htmlspecialchars(strtoupper($v['categorie'])) ?>
        </div>

        <!-- Name -->
        <div class="vcard-name"><?= htmlspecialchars(strtoupper($v['marque']) . ' ' . strtoupper($v['modele'])) ?></div>
        <div class="vcard-sub">
          <?= htmlspecialchars($v['immatriculation'] ?: $v['numero']) ?>
          <?php if ($v['annee']): ?> · <?= $v['annee'] ?><?php endif; ?>
          <?php if ($v['couleur']): ?> · <?= htmlspecialchars($v['couleur']) ?><?php endif; ?>
        </div>

        <!-- Specs -->
        <div class="vcard-specs">
          <?php if ($v['nb_places']): ?>
          <span class="spec-chip"><span class="spec-icon">👤</span><?= $v['nb_places'] ?> places</span>
          <?php endif; ?>
          <span class="spec-chip"><span class="spec-icon">📍</span><?= number_format($v['kilometrage']) ?> km</span>
          <span class="spec-chip"><span class="spec-icon">⚙️</span>Auto</span>
          <span class="spec-chip"><span class="spec-icon">❄️</span>Climatisation</span>
        </div>

        <!-- Includes -->
        <div class="vcard-includes">
          <div class="incl-item">
            <div class="incl-check">
              <svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            Kilométrage inclus
          </div>
          <div class="incl-item">
            <div class="incl-check">
              <svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            Protection de base incluse
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="vcard-actions">
        <a href="/location/public/index.php?url=vehicles/edit&id=<?= $v['id'] ?>"
           class="btn btn-outline-secondary btn-edit-sm">✏️ Modifier</a>
        <a href="/location/public/index.php?url=vehicles/delete&id=<?= $v['id'] ?>"
           class="btn btn-outline-danger btn-edit-sm"
           onclick="return confirm('Supprimer ce véhicule ?')">🗑 Supprimer</a>
        <a href="/location/public/index.php?url=reservations/add&vehicle_id=<?= $v['id'] ?>"
           class="btn-details ms-auto">Plus de détails ▼</a>
      </div>
    </div>

    <!-- Price -->
    <div class="vcard-price">
      <div class="price-label">Payer en agence</div>
      <div>
        <div class="price-amount">
          <?= number_format($v['prix_jour'], 2, ',', ' ') ?> <span>MAD</span>
        </div>
        <div class="price-total">/ jour</div>
      </div>
      <?php if ($v['caution'] > 0): ?>
      <div class="price-caution">Caution : <?= number_format($v['caution'], 2, ',', ' ') ?> MAD</div>
      <?php endif; ?>
      <?php if ($isDisponible): ?>
        <a href="/location/public/index.php?url=reservations/add&vehicle_id=<?= $v['id'] ?>"
           class="btn-select">Sélectionner</a>
      <?php else: ?>
        <button class="btn-select" style="background:linear-gradient(135deg,#9ca3af,#6b7280);box-shadow:none;cursor:not-allowed" disabled>
          Indisponible
        </button>
      <?php endif; ?>
    </div>

  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
