<?php
require_once "../config/database.php";
require_once "../models/PaiementModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new PaiementModel($conn);

$preResId = (int)($_GET['reservation_id'] ?? 0);
$errors   = [];

// Réservations actives
$reservations = $conn->query("
    SELECT r.id, r.reference, r.montant_total,
           c.nom, c.prenom,
           COALESCE((SELECT SUM(p.montant) FROM paiements p WHERE p.reservation_id=r.id),0) AS total_paye
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    WHERE r.statut NOT IN ('annulée')
    ORDER BY r.created_at DESC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resId   = (int)$_POST['reservation_id'];
    $montant = (float)$_POST['montant'];
    $date    = $_POST['date_paiement'];

    if (!$resId)   $errors[] = 'Sélectionner une réservation.';
    if ($montant <= 0) $errors[] = 'Montant invalide.';
    if (!$date)    $errors[] = 'Date requise.';

    if (!$errors) {
        $model->create([
            ':reservation_id'       => $resId,
            ':montant'              => $montant,
            ':type_paiement'        => $_POST['type_paiement'],
            ':type'                 => $_POST['type'],
            ':reference_transaction'=> trim($_POST['reference_transaction']) ?: null,
            ':date_paiement'        => $date,
            ':notes'                => trim($_POST['notes']) ?: null,
        ]);
        flash('success', 'Paiement de ' . number_format($montant, 2) . ' MAD enregistré.');
        header("Location: /location/reservations/view.php?id=$resId");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Enregistrer un paiement</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:620px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="index.php" class="text-decoration-none text-muted">Paiements</a> / Nouveau</div>
      <h1 class="page-title">Enregistrer un paiement</h1>
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
      <form method="POST" class="row g-3">

        <div class="col-12">
          <label class="form-label fw-semibold">Réservation <span class="text-danger">*</span></label>
          <select name="reservation_id" class="form-select" required onchange="fillInfo(this)">
            <option value="">— Sélectionner —</option>
            <?php foreach ($reservations as $res): ?>
              <option value="<?= $res['id'] ?>"
                      data-total="<?= $res['montant_total'] ?>"
                      data-paye="<?= $res['total_paye'] ?>"
                      <?= ($preResId == $res['id'] || ($_POST['reservation_id'] ?? 0) == $res['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($res['reference']) ?> — <?= htmlspecialchars($res['nom'] . ' ' . $res['prenom']) ?>
                (<?= number_format($res['montant_total'], 2) ?> MAD)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12" id="infoPane" style="display:none">
          <div class="alert alert-info py-2 mb-0">
            Total facturé : <strong id="totalFact">—</strong> · Déjà payé : <strong id="dejaP">—</strong> · Reste : <strong id="reste" class="text-danger">—</strong>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Type de paiement</label>
          <select name="type" class="form-select">
            <?php foreach (['acompte','solde','caution','remboursement','frais extra'] as $t): ?>
              <option value="<?= $t ?>" <?= ($_POST['type'] ?? 'solde') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Mode de règlement</label>
          <select name="type_paiement" class="form-select">
            <?php foreach (['espèces','carte bancaire','virement','chèque'] as $m): ?>
              <option <?= ($_POST['type_paiement'] ?? 'espèces') === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Montant (MAD) <span class="text-danger">*</span></label>
          <input type="number" name="montant" class="form-control" step="0.01" min="0.01"
                 value="<?= htmlspecialchars($_POST['montant'] ?? '') ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Date du paiement <span class="text-danger">*</span></label>
          <input type="date" name="date_paiement" class="form-control"
                 value="<?= htmlspecialchars($_POST['date_paiement'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Référence de transaction</label>
          <input name="reference_transaction" class="form-control" placeholder="N° reçu, TPE, virement…"
                 value="<?= htmlspecialchars($_POST['reference_transaction'] ?? '') ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Notes</label>
          <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-success px-4">Enregistrer</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillInfo(sel) {
  const opt = sel.options[sel.selectedIndex];
  const pane = document.getElementById('infoPane');
  if (!opt.value) { pane.style.display = 'none'; return; }
  const total = parseFloat(opt.dataset.total) || 0;
  const paye  = parseFloat(opt.dataset.paye)  || 0;
  document.getElementById('totalFact').textContent = total.toFixed(2) + ' MAD';
  document.getElementById('dejaP').textContent     = paye.toFixed(2)  + ' MAD';
  document.getElementById('reste').textContent     = (total - paye).toFixed(2) + ' MAD';
  pane.style.display = 'block';
}
document.addEventListener('DOMContentLoaded', () => {
  const sel = document.querySelector('[name=reservation_id]');
  if (sel.value) fillInfo(sel);
});
</script>
</body>
</html>
