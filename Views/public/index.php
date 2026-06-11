<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= getLanguages()[$lang]['dir'] ?? 'ltr' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= __('reservation_en_ligne') ?> — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <style>
    :root { --accent: #f97316; --primary: #1a3a5c; }
    body { background: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; }
    .rtl { direction: rtl; text-align: right; }
    .rtl .filter-bar .row, .rtl .vehicles-grid { direction: rtl; }

    .hero {
      background: linear-gradient(135deg, #1a3a5c 0%, #2563eb 100%);
      color: #fff; padding: 60px 0 40px; text-align: center;
    }
    .hero h1 { font-size: 2.2rem; font-weight: 800; margin-bottom: 8px; }
    .hero p { font-size: 1.1rem; opacity: .85; }

    .lang-switcher { position: fixed; top: 16px; right: 16px; z-index: 999; display: flex; gap: 4px; }
    .lang-switcher a {
      background: rgba(255,255,255,.15); backdrop-filter: blur(8px);
      color: #fff; padding: 6px 12px; border-radius: 8px;
      font-size: 13px; font-weight: 600; text-decoration: none;
      transition: background .2s;
    }
    .lang-switcher a:hover, .lang-switcher a.active { background: rgba(255,255,255,.3); }
    .lang-switcher a.active { background: var(--accent); }

    .booking-wrapper { max-width: 1300px; margin: -20px auto 40px; padding: 0 20px; position: relative; z-index: 10; }
    .booking-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.1); padding: 0; overflow: hidden; }

    .booking-header {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      padding: 20px 28px; border-bottom: 2px solid var(--accent);
    }
    .booking-header h2 { font-size: 1.3rem; font-weight: 700; color: var(--primary); margin: 0; }
    .booking-body { padding: 28px; display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }
    @media (max-width: 992px) { .booking-body { grid-template-columns: 1fr; } }

    .date-inputs { display: flex; gap: 12px; margin-bottom: 16px; }
    .date-inputs input { flex: 1; }

    .filter-bar { background: #f8fafc; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; }
    .v-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }

    .vehicle-card {
      background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
      overflow: hidden; transition: transform .2s, box-shadow .2s; cursor: pointer;
    }
    .vehicle-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }
    .vehicle-card.selected { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(249,115,22,.2); }
    .vehicle-img { height: 160px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; position: relative; }
    .vehicle-img .placeholder { font-size: 60px; }
    .vehicle-img .badge-dispo { position: absolute; top: 10px; right: 10px; background: #10b981; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .vehicle-info { padding: 14px 16px; }
    .vehicle-info h3 { font-size: 16px; font-weight: 700; margin: 0 0 2px; }
    .vehicle-info .meta { font-size: 13px; color: #6b7280; margin-bottom: 8px; }
    .vehicle-info .price { font-size: 22px; font-weight: 900; color: var(--primary); }
    .vehicle-info .price span { font-size: 13px; font-weight: 600; color: #9ca3af; }

    .btn-reserve {
      background: linear-gradient(135deg, var(--accent), #ea6c0a);
      color: #fff; border: none; border-radius: 10px; padding: 14px 32px;
      font-size: 16px; font-weight: 700; width: 100%; cursor: pointer;
      transition: transform .15s, box-shadow .15s;
    }
    .btn-reserve:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(249,115,22,.3); }
    .btn-reserve:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }

    .form-control, .form-select { border-radius: 10px; padding: 10px 14px; border: 1px solid #e5e7eb; }
    .form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(249,115,22,.1); }

    #calendar { max-height: 450px; }
    .fc .fc-toolbar-title { font-size: 1rem !important; }
    .fc .fc-button { font-size: 13px !important; border-radius: 8px !important; }

    .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
    .alert-error li { margin: 2px 0; }

    @media (max-width: 768px) {
      .hero h1 { font-size: 1.5rem; }
      .hero p { font-size: .95rem; }
      .booking-body { padding: 16px; }
      .date-inputs { flex-direction: column; }
    }
  </style>
</head>
<body class="<?= $lang === 'ar' ? 'rtl' : '' ?>">

<div class="lang-switcher">
  <?php foreach (getLanguages() as $code => $l): ?>
    <a href="?lang=<?= $code ?>" class="<?= $lang === $code ? 'active' : '' ?>"><?= $l['flag'] ?></a>
  <?php endforeach; ?>
</div>

<section class="hero">
  <div class="container">
    <h1><?= __('reservation_en_ligne') ?></h1>
    <p><?= __('choisir_vehicule') ?></p>
  </div>
</section>

<div class="booking-wrapper">
  <?php if (!empty($_SESSION['booking_errors'])): ?>
    <div class="alert-error">
      <ul class="mb-0">
        <?php foreach ($_SESSION['booking_errors'] as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php unset($_SESSION['booking_errors']); ?>
  <?php endif; ?>

  <div class="booking-card">
    <div class="booking-header">
      <h2><i class="fas fa-car"></i> <?= __('choisir_vehicule') ?></h2>
    </div>
    <div class="booking-body">
      <!-- Left: Vehicle selection -->
      <div>
        <form method="GET" action="/location/public/index.php" class="filter-bar">
          <input type="hidden" name="url" value="public">
          <div class="row g-2">
            <div class="col-md-6">
              <input type="text" name="q" class="form-control" placeholder="<?= __('rechercher') ?>..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <select name="categorie" class="form-select">
                <option value=""><?= __('categorie') ?></option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat ?>" <?= ($_GET['categorie'] ?? '') === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <button class="btn btn-primary w-100" style="border-radius:10px"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </form>

        <div class="v-grid">
          <?php foreach ($vehicles as $v): ?>
            <div class="vehicle-card" data-id="<?= $v['id'] ?>" onclick="selectVehicle(this, <?= $v['id'] ?>, <?= $v['prix_jour'] ?>, <?= $v['caution'] ?>)">
              <div class="vehicle-img">
                <span class="badge-dispo"><?= __('disponible') ?></span>
                <?php if (!empty($v['image'])): ?>
                  <img src="/location/uploads/vehicles/<?= htmlspecialchars($v['image']) ?>" alt="<?= htmlspecialchars($v['marque'] . ' ' . $v['modele']) ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <span class="placeholder">🚗</span>
                <?php endif; ?>
              </div>
              <div class="vehicle-info">
                <h3><?= htmlspecialchars($v['marque'] . ' ' . $v['modele']) ?></h3>
                <div class="meta"><?= $v['annee'] ?? '' ?> • <?= htmlspecialchars($v['immatriculation'] ?: $v['numero']) ?></div>
                <div class="price"><?= number_format($v['prix_jour'], 0, ',', ' ') ?> <span>MAD / <?= __('prix_jour') ?></span></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($vehicles)): ?>
            <div class="text-center py-4" style="grid-column:1/-1;color:#9ca3af">
              <div style="font-size:48px;margin-bottom:8px">🚗</div>
              <p><?= __('aucun_resultat') ?></p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right: Calendar + Booking form -->
      <div>
        <div id="calendar"></div>

        <form method="POST" action="/location/public/index.php?url=public/book" style="margin-top:20px">
          <input type="hidden" name="vehicle_id" id="selectedVehicleId" value="">
          <div class="date-inputs">
            <div>
              <label class="form-label small fw-bold text-muted"><?= __('date_debut') ?></label>
              <input type="date" name="date_debut" id="dateDebut" class="form-control" required
                     value="<?= htmlspecialchars($_SESSION['booking_data']['date_debut'] ?? '') ?>"
                     min="<?= date('Y-m-d') ?>">
            </div>
            <div>
              <label class="form-label small fw-bold text-muted"><?= __('date_fin') ?></label>
              <input type="date" name="date_fin" id="dateFin" class="form-control" required
                     value="<?= htmlspecialchars($_SESSION['booking_data']['date_fin'] ?? '') ?>"
                     min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
          </div>

          <div id="pricePreview" style="display:none;background:#f0fdf4;border-radius:10px;padding:12px 16px;margin-bottom:16px;text-align:center">
            <div style="font-size:13px;color:#6b7280"><?= __('total_estime') ?></div>
            <div style="font-size:24px;font-weight:900;color:#059669" id="totalPrice">0,00 <span style="font-size:14px">MAD</span></div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted"><?= __('nom_complet') ?></label>
            <input type="text" name="nom" class="form-control" required
                   value="<?= htmlspecialchars($_SESSION['booking_data']['nom'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted"><?= __('email') ?></label>
            <input type="email" name="email" class="form-control" required
                   value="<?= htmlspecialchars($_SESSION['booking_data']['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted"><?= __('telephone') ?></label>
            <input type="tel" name="telephone" class="form-control"
                   value="<?= htmlspecialchars($_SESSION['booking_data']['telephone'] ?? '') ?>">
          </div>
          <?php unset($_SESSION['booking_data']); ?>
          <button type="submit" class="btn-reserve" id="btnReserve" disabled>
            <i class="fas fa-calendar-check"></i> <?= __('reserver') ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
let selectedVehicleId = 0, selectedPrice = 0, selectedCaution = 0;
const calendarEl = document.getElementById('calendar');

let calendar = new FullCalendar.Calendar(calendarEl, {
  initialView: 'dayGridMonth',
  height: 'auto',
  locale: '<?= $lang === 'ar' ? 'ar' : ($lang === 'en' ? 'en' : 'fr') ?>',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: ''
  },
  selectable: true,
  select: function(info) {
    document.getElementById('dateDebut').value = info.startStr;
    document.getElementById('dateFin').value = info.endStr;
    updatePrice();
  },
  events: '/location/public/index.php?url=public/calendar',
  eventSources: []
});
calendar.render();

function selectVehicle(el, id, price, caution) {
  document.querySelectorAll('.vehicle-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  selectedVehicleId = id;
  selectedPrice = price;
  selectedCaution = caution;
  document.getElementById('selectedVehicleId').value = id;
  document.getElementById('btnReserve').disabled = false;

  // Update calendar to show this vehicle's availability
  calendar.removeAllEventSources();
  calendar.addEventSource('/location/public/index.php?url=public/calendar&vehicle_id=' + id);
  updatePrice();
}

function updatePrice() {
  const start = document.getElementById('dateDebut').value;
  const end = document.getElementById('dateFin').value;
  const preview = document.getElementById('pricePreview');

  if (start && end && selectedPrice > 0) {
    const d1 = new Date(start);
    const d2 = new Date(end);
    const days = Math.max(1, Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)));
    const total = days * selectedPrice;
    document.getElementById('totalPrice').innerHTML = total.toLocaleString('fr-FR', {minimumFractionDigits:2}) + ' <span style="font-size:14px">MAD</span>';
    preview.style.display = 'block';
  } else {
    preview.style.display = 'none';
  }
}

document.getElementById('dateDebut').addEventListener('change', updatePrice);
document.getElementById('dateFin').addEventListener('change', updatePrice);
</script>
</body>
</html>
