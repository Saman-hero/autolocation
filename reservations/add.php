<?php
require_once "../config/database.php";
require_once "../models/ReservationModel.php";
require_once "../models/VehicleModel.php";
require_once "../models/ClientModel.php";
require_once "../includes/audit.php";
require_once "../includes/mailer.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ReservationModel($conn);

$clients  = (new ClientModel($conn))->getAll(['statut' => 'actif']);
$vehicles = (new VehicleModel($conn))->getAvailable();

$preClientId = (int)($_GET['client_id'] ?? 0);
$errors = [];

$autoRef = $model->generateReference();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId  = (int)$_POST['client_id'];
    $vehicleId = (int)$_POST['vehicle_id'];
    $debut     = $_POST['date_debut'];
    $fin       = $_POST['date_fin_prevue'];
    $prixJour  = (float)$_POST['prix_jour'];

    if (!$clientId)   $errors[] = 'Sélectionner un client.';
    if (!$vehicleId)  $errors[] = 'Sélectionner un véhicule.';
    if (!$debut)      $errors[] = 'Date de début requise.';
    if (!$fin)        $errors[] = 'Date de fin requise.';
    if ($fin <= $debut) $errors[] = 'La date de fin doit être après la date de début.';

    if (!$errors) {
        $nbJours = max(1, (int)ceil((strtotime($fin) - strtotime($debut)) / 86400));
        $montant = $nbJours * $prixJour;
        $ref     = trim($_POST['reference']) ?: $autoRef;

        $resId = $model->create([
            ':reference'       => $ref,
            ':client_id'       => $clientId,
            ':vehicle_id'      => $vehicleId,
            ':statut'          => $_POST['statut'] ?: 'confirmée',
            ':date_debut'      => $debut,
            ':date_fin_prevue' => $fin,
            ':lieu_depart'     => trim($_POST['lieu_depart']) ?: null,
            ':lieu_retour'     => trim($_POST['lieu_retour']) ?: null,
            ':prix_jour'       => $prixJour,
            ':nb_jours'        => $nbJours,
            ':caution'         => (float)($_POST['caution'] ?: 0),
            ':montant_total'   => $montant,
            ':commentaire'     => trim($_POST['commentaire']) ?: null,
            ':created_by'      => $_SESSION['user_id'] ?? null,
        ]);

        // Marquer le véhicule comme loué si statut = en cours
        if ($_POST['statut'] === 'en cours') {
            $conn->prepare("UPDATE vehicles SET statut='loué' WHERE id=?")->execute([$vehicleId]);
        }

        // Audit
        $clientName = '';
        $vNum = '';
        foreach ($clients as $cl) { if ($cl['id'] == $clientId) { $clientName = $cl['nom'] . ' ' . $cl['prenom']; break; } }
        foreach ($vehicles as $vv) { if ($vv['id'] == $vehicleId) { $vNum = $vv['numero']; break; } }
        audit_log($conn, 'CREATE', 'reservations', $resId, "Réservation $ref créée pour $clientName — véhicule $vNum");

        // Email de confirmation
        try {
            $clientFull = $conn->prepare("SELECT * FROM clients WHERE id=?")->execute([$clientId]) ? $conn->query("SELECT * FROM clients WHERE id=$clientId")->fetch() : null;
            $vehicleFull = $conn->prepare("SELECT * FROM vehicles WHERE id=?")->execute([$vehicleId]) ? $conn->query("SELECT * FROM vehicles WHERE id=$vehicleId")->fetch() : null;
            $resFull = $conn->prepare("SELECT * FROM reservations WHERE id=?")->execute([$resId]) ? $conn->query("SELECT * FROM reservations WHERE id=$resId")->fetch() : null;
            if ($clientFull && $vehicleFull && $resFull) {
                sendReservationConfirmation($clientFull, $resFull, $vehicleFull);
            }
        } catch (Exception $e) { /* Email non critique */ }

        flash('success', "Réservation $ref créée avec succès.");
        header("Location: view.php?id=$resId");
        exit;
    }
}

// Prix auto selon véhicule sélectionné
$vehiclesPrices = [];
foreach ($vehicles as $v) {
    $vehiclesPrices[$v['id']] = ['prix' => $v['prix_jour'], 'caution' => $v['caution']];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouvelle location</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:800px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="index.php" class="text-decoration-none text-muted">Réservations</a> / Nouvelle</div>
      <h1 class="page-title">Nouvelle location</h1>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <?php if ($errors): ?>
  <div class="alert alert-danger mb-3">
    <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3" id="resForm">

        <div class="col-md-6">
          <label class="form-label fw-semibold">Référence</label>
          <input name="reference" class="form-control" value="<?= htmlspecialchars($_POST['reference'] ?? $autoRef) ?>">
          <div class="form-text">Générée automatiquement — modifiable.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Statut initial</label>
          <select name="statut" class="form-select">
            <option value="en attente" <?= ($_POST['statut'] ?? '') === 'en attente' ? 'selected' : '' ?>>En attente</option>
            <option value="confirmée"  <?= ($_POST['statut'] ?? 'confirmée') === 'confirmée' ? 'selected' : '' ?>>Confirmée</option>
            <option value="en cours"   <?= ($_POST['statut'] ?? '') === 'en cours'  ? 'selected' : '' ?>>En cours (départ immédiat)</option>
          </select>
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Client &amp; Véhicule</strong></div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Client <span class="text-danger">*</span></label>
          <select name="client_id" id="clientSelect" class="form-select" required onchange="checkClientLicense(this.value)">
            <option value="">— Sélectionner un client —</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (($preClientId ?: ($_POST['client_id'] ?? 0)) == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div id="licenseAlert" style="display:none" class="mt-2"></div>
          <input type="hidden" id="licenseBlocked" name="license_blocked" value="0">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Véhicule <span class="text-danger">*</span></label>
          <select name="vehicle_id" class="form-select" required onchange="fillTarif(this)">
            <option value="">— Sélectionner un véhicule —</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= $v['id'] ?>"
                      data-prix="<?= $v['prix_jour'] ?>"
                      data-caution="<?= $v['caution'] ?>"
                      <?= (($_POST['vehicle_id'] ?? 0) == $v['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['numero'] . ' — ' . $v['marque'] . ' ' . $v['modele']) ?>
                (<?= number_format($v['prix_jour'], 2) ?> MAD/j)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Dates &amp; Lieux</strong></div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Date de début <span class="text-danger">*</span></label>
          <input type="datetime-local" name="date_debut" class="form-control" required
                 value="<?= htmlspecialchars($_POST['date_debut'] ?? '') ?>" onchange="calcTotal()">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Date de fin prévue <span class="text-danger">*</span></label>
          <input type="datetime-local" name="date_fin_prevue" class="form-control" required
                 value="<?= htmlspecialchars($_POST['date_fin_prevue'] ?? '') ?>" onchange="calcTotal()">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Lieu de départ</label>
          <input name="lieu_depart" class="form-control" placeholder="Ex: Agence Casablanca" value="<?= htmlspecialchars($_POST['lieu_depart'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Lieu de retour</label>
          <input name="lieu_retour" class="form-control" placeholder="Ex: Agence Rabat" value="<?= htmlspecialchars($_POST['lieu_retour'] ?? '') ?>">
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Tarification</strong></div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Prix par jour (MAD)</label>
          <input type="number" name="prix_jour" id="prix_jour" class="form-control" step="0.01" min="0"
                 value="<?= htmlspecialchars($_POST['prix_jour'] ?? '0') ?>" onchange="calcTotal()">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Caution (MAD)</label>
          <input type="number" name="caution" id="caution" class="form-control" step="0.01" min="0"
                 value="<?= htmlspecialchars($_POST['caution'] ?? '0') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Total estimé</label>
          <div class="form-control bg-light fw-bold text-primary" id="totalDisplay">—</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Commentaire</label>
          <textarea name="commentaire" class="form-control" rows="2"><?= htmlspecialchars($_POST['commentaire'] ?? '') ?></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-success px-4">Créer la réservation</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillTarif(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (opt.dataset.prix) {
    document.getElementById('prix_jour').value = opt.dataset.prix;
    document.getElementById('caution').value   = opt.dataset.caution;
    calcTotal();
  }
}

function calcTotal() {
  const debut = new Date(document.querySelector('[name=date_debut]').value);
  const fin   = new Date(document.querySelector('[name=date_fin_prevue]').value);
  const prix  = parseFloat(document.getElementById('prix_jour').value) || 0;
  if (debut && fin && fin > debut && prix > 0) {
    const jours = Math.ceil((fin - debut) / 86400000);
    document.getElementById('totalDisplay').textContent = (jours * prix).toFixed(2) + ' MAD (' + jours + ' j)';
  } else {
    document.getElementById('totalDisplay').textContent = '—';
  }
}

function checkClientLicense(clientId) {
  const alertEl   = document.getElementById('licenseAlert');
  const blockedEl = document.getElementById('licenseBlocked');
  const submitBtn = document.querySelector('[type=submit]');
  if (!clientId) { alertEl.style.display = 'none'; blockedEl.value = '0'; return; }

  fetch('/location/api/client-info.php?id=' + clientId)
    .then(r => r.json()).then(data => {
      if (data.error) { alertEl.style.display = 'none'; return; }
      if (data.permis_status === 'expired') {
        alertEl.innerHTML = '<div class="alert alert-danger py-2 mb-0"><strong>🚫 Permis expiré !</strong> ' + data.permis_msg + '<br><small>La soumission du formulaire est bloquée.</small></div>';
        alertEl.style.display = 'block';
        blockedEl.value = '1';
        submitBtn.disabled = true;
        submitBtn.title = 'Permis du client expiré';
      } else if (data.permis_status === 'expiring') {
        alertEl.innerHTML = '<div class="alert alert-warning py-2 mb-0"><strong>⚠ Attention !</strong> ' + data.permis_msg + '</div>';
        alertEl.style.display = 'block';
        blockedEl.value = '0';
        submitBtn.disabled = false;
      } else {
        alertEl.style.display = 'none';
        blockedEl.value = '0';
        submitBtn.disabled = false;
      }
    }).catch(() => { alertEl.style.display = 'none'; });
}

// Check on load if client pre-selected
document.addEventListener('DOMContentLoaded', function() {
  const sel = document.getElementById('clientSelect');
  if (sel && sel.value) checkClientLicense(sel.value);

  // Block form if license is already blocked
  document.getElementById('resForm').addEventListener('submit', function(e) {
    if (document.getElementById('licenseBlocked').value === '1') {
      e.preventDefault();
      alert('Impossible de créer la réservation : le permis du client est expiré.');
    }
  });
});
</script>
</body>
</html>
