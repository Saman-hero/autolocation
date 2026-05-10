<?php
require_once "../config/database.php";
require_once "../models/ReservationModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ReservationModel($conn);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$r = $conn->prepare("SELECT * FROM reservations WHERE id=?");
$r->execute([$id]);
$r = $r->fetch();
if (!$r || $r['statut'] !== 'en cours') {
    flash('danger', 'Réservation introuvable ou non en cours.');
    header("Location: index.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kmRetour   = $_POST['km_retour'] !== '' ? (int)$_POST['km_retour'] : null;
    $fraisExtra = (float)($_POST['frais_extra'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');

    // Recalculer durée réelle et total
    $now       = new DateTime();
    $debut     = new DateTime($r['date_debut']);
    $nbJoursReel = max(1, (int)ceil(($now->getTimestamp() - $debut->getTimestamp()) / 86400));
    $total     = $nbJoursReel * $r['prix_jour'] + $fraisExtra;

    $conn->prepare("
        UPDATE reservations SET
            statut               = 'terminée',
            date_retour_effectif = NOW(),
            km_retour            = ?,
            frais_extra          = ?,
            montant_total        = ?,
            commentaire          = ?
        WHERE id=?
    ")->execute([$kmRetour, $fraisExtra, $total, $commentaire ?: $r['commentaire'], $id]);

    // Remettre le véhicule disponible
    $conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=?")
         ->execute([$r['vehicle_id']]);

    // Mettre à jour km véhicule si fourni
    if ($kmRetour) {
        $conn->prepare("UPDATE vehicles SET kilometrage=? WHERE id=? AND kilometrage < ?")
             ->execute([$kmRetour, $r['vehicle_id'], $kmRetour]);
    }

    flash('success', 'Location clôturée. Total : ' . number_format($total, 2) . ' MAD.');
    header("Location: view.php?id=$id");
    exit;
}

// Calcul estimé
$now   = new DateTime();
$debut = new DateTime($r['date_debut']);
$joursEcoules = max(1, (int)ceil(($now->getTimestamp() - $debut->getTimestamp()) / 86400));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Clôturer location <?= htmlspecialchars($r['reference']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:600px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="view.php?id=<?= $id ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($r['reference']) ?></a> / Clôturer</div>
      <h1 class="page-title">Retour du véhicule</h1>
    </div>
    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <div class="alert alert-info mb-3">
    Durée écoulée : <strong><?= $joursEcoules ?> jour(s)</strong> ·
    Total estimé : <strong><?= number_format($joursEcoules * $r['prix_jour'], 2) ?> MAD</strong>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="col-md-6">
          <label class="form-label fw-semibold">Kilométrage au retour</label>
          <input type="number" name="km_retour" class="form-control" min="<?= $r['km_depart'] ?: 0 ?>"
                 placeholder="Ex: <?= ($r['km_depart'] ?: 0) + 500 ?>" onchange="calcFinal()">
          <?php if ($r['km_depart']): ?>
            <div class="form-text">Km au départ : <?= number_format($r['km_depart']) ?> km</div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Frais extra (MAD)</label>
          <input type="number" name="frais_extra" id="fraisExtra" class="form-control"
                 step="0.01" min="0" value="0" onchange="calcFinal()" placeholder="Carburant, dégâts…">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Total final estimé</label>
          <div class="form-control bg-light fw-bold text-primary" id="totalFinal">
            <?= number_format($joursEcoules * $r['prix_jour'], 2) ?> MAD
          </div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Commentaire de clôture</label>
          <textarea name="commentaire" class="form-control" rows="3" placeholder="État du véhicule, remarques…"></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-warning px-4 fw-semibold">✔ Confirmer le retour</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const joursEcoules = <?= $joursEcoules ?>;
const prixJour = <?= $r['prix_jour'] ?>;

function calcFinal() {
  const extra = parseFloat(document.getElementById('fraisExtra').value) || 0;
  const total = joursEcoules * prixJour + extra;
  document.getElementById('totalFinal').textContent = total.toFixed(2) + ' MAD';
}
</script>
</body>
</html>
