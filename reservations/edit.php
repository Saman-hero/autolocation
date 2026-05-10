<?php
require_once "../config/database.php";
require_once "../models/ReservationModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ReservationModel($conn);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }
$r = $model->getById($id);
if (!$r || !in_array($r['statut'], ['en attente','confirmée'])) {
    flash('danger', 'Cette réservation ne peut pas être modifiée.');
    header("Location: view.php?id=$id"); exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debut    = $_POST['date_debut'];
    $fin      = $_POST['date_fin_prevue'];
    $prixJour = (float)$_POST['prix_jour'];

    if (!$debut || !$fin || $fin <= $debut) $errors[] = 'Dates invalides.';

    if (!$errors) {
        $nbJours = max(1, (int)ceil((strtotime($fin) - strtotime($debut)) / 86400));
        $montant = $nbJours * $prixJour;
        $model->update([
            ':id'              => $id,
            ':statut'          => $_POST['statut'],
            ':date_debut'      => $debut,
            ':date_fin_prevue' => $fin,
            ':lieu_depart'     => trim($_POST['lieu_depart']) ?: null,
            ':lieu_retour'     => trim($_POST['lieu_retour']) ?: null,
            ':prix_jour'       => $prixJour,
            ':nb_jours'        => $nbJours,
            ':caution'         => (float)($_POST['caution'] ?: 0),
            ':montant_total'   => $montant,
            ':commentaire'     => trim($_POST['commentaire']) ?: null,
        ]);
        flash('success', 'Réservation mise à jour.');
        header("Location: view.php?id=$id");
        exit;
    }
}

$d = array_merge($r, $_POST);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier réservation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:700px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="view.php?id=<?= $id ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($r['reference']) ?></a> / Modifier</div>
      <h1 class="page-title">Modifier la réservation</h1>
    </div>
    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <?php if ($errors): ?>
  <div class="alert alert-danger mb-3">
    <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3">

        <div class="col-md-6">
          <label class="form-label fw-semibold">Statut</label>
          <select name="statut" class="form-select">
            <option value="en attente" <?= $d['statut'] === 'en attente' ? 'selected' : '' ?>>En attente</option>
            <option value="confirmée"  <?= $d['statut'] === 'confirmée'  ? 'selected' : '' ?>>Confirmée</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Client</label>
          <div class="form-control bg-light"><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Date de début</label>
          <input type="datetime-local" name="date_debut" class="form-control"
                 value="<?= str_replace(' ','T', $d['date_debut']) ?>" required onchange="calcTotal()">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Date de fin prévue</label>
          <input type="datetime-local" name="date_fin_prevue" class="form-control"
                 value="<?= str_replace(' ','T', $d['date_fin_prevue']) ?>" required onchange="calcTotal()">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Lieu de départ</label>
          <input name="lieu_depart" class="form-control" value="<?= htmlspecialchars($d['lieu_depart'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Lieu de retour</label>
          <input name="lieu_retour" class="form-control" value="<?= htmlspecialchars($d['lieu_retour'] ?? '') ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Prix / jour (MAD)</label>
          <input type="number" name="prix_jour" id="prix_jour" class="form-control" step="0.01"
                 value="<?= $d['prix_jour'] ?? 0 ?>" onchange="calcTotal()">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Caution (MAD)</label>
          <input type="number" name="caution" class="form-control" step="0.01" value="<?= $d['caution'] ?? 0 ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Total estimé</label>
          <div class="form-control bg-light fw-bold text-primary" id="totalDisplay">
            <?= number_format($d['montant_total'] ?? 0, 2) ?> MAD
          </div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Commentaire</label>
          <textarea name="commentaire" class="form-control" rows="2"><?= htmlspecialchars($d['commentaire'] ?? '') ?></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-primary px-4">Enregistrer</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function calcTotal() {
  const debut = new Date(document.querySelector('[name=date_debut]').value);
  const fin   = new Date(document.querySelector('[name=date_fin_prevue]').value);
  const prix  = parseFloat(document.getElementById('prix_jour').value) || 0;
  if (debut && fin && fin > debut && prix > 0) {
    const jours = Math.ceil((fin - debut) / 86400000);
    document.getElementById('totalDisplay').textContent = (jours * prix).toFixed(2) + ' MAD (' + jours + ' j)';
  }
}
</script>
</body>
</html>
