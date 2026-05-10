<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$vehicles       = $conn->query("SELECT id, numero, marque, modele, kilometrage FROM vehicles ORDER BY numero")->fetchAll();
$preVehicleId   = (int)($_GET['vehicle_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId  = (int)$_POST['vehicle_id'];
    $type       = trim($_POST['type_maintenance']);
    $desc       = trim($_POST['description'] ?? '');
    $date       = $_POST['date_maintenance'];
    $km         = $_POST['kilometrage_intervention'] !== '' ? (int)$_POST['kilometrage_intervention'] : null;
    $cout       = $_POST['cout'] !== '' ? (float)$_POST['cout'] : null;
    $technicien = trim($_POST['technicien'] ?? '');
    $statut     = $_POST['statut'];

    $conn->prepare("
        INSERT INTO maintenance
            (vehicle_id, type_maintenance, description, date_maintenance,
             kilometrage_intervention, cout, technicien, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([$vehicleId, $type, $desc, $date, $km, $cout, $technicien, $statut]);

    // Si terminée et que le km est fourni, mettre à jour derniere_vidange_km du véhicule
    if ($statut === 'terminée' && $km && strtolower($type) === 'vidange') {
        $conn->prepare("
            UPDATE vehicles
            SET derniere_vidange_km = ?, date_derniere_vidange = ?
            WHERE id = ?
        ")->execute([$km, $date, $vehicleId]);
    }

    // Si en cours ou planifiée, passer le véhicule en maintenance
    if (in_array($statut, ['planifiée', 'en cours'])) {
        $conn->prepare("UPDATE vehicles SET statut='maintenance' WHERE id=? AND statut='disponible'")
             ->execute([$vehicleId]);
    }
    // Si terminée, remettre disponible
    if ($statut === 'terminée') {
        $conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=? AND statut='maintenance'")
             ->execute([$vehicleId]);
    }

    flash('success', 'Maintenance enregistrée avec succès.');
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouvelle maintenance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:700px">

  <div class="page-header">
    <h1 class="page-title">🔧 Nouvelle maintenance</h1>
    <a href="index.php" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3">

        <div class="col-12">
          <label class="form-label">Véhicule <span class="text-danger">*</span></label>
          <select name="vehicle_id" id="vehicleSelect" class="form-select" required onchange="fillKm()">
            <option value="">— Sélectionner un véhicule —</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= $v['id'] ?>"
                      data-km="<?= $v['kilometrage'] ?>"
                      <?= $v['id'] == $preVehicleId ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['numero'] . ' — ' . $v['marque'] . ' ' . $v['modele']) ?>
                (<?= number_format($v['kilometrage']) ?> km)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Type de maintenance <span class="text-danger">*</span></label>
          <select name="type_maintenance" class="form-select" required>
            <option value="">— Sélectionner —</option>
            <option value="Vidange">Vidange</option>
            <option value="Révision générale">Révision générale</option>
            <option value="Changement de pneus">Changement de pneus</option>
            <option value="Freinage">Freinage</option>
            <option value="Batterie">Batterie</option>
            <option value="Climatisation">Climatisation</option>
            <option value="Réparation moteur">Réparation moteur</option>
            <option value="Carrosserie">Carrosserie</option>
            <option value="Autre">Autre</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Statut</label>
          <select name="statut" class="form-select">
            <option value="planifiée">Planifiée</option>
            <option value="en cours">En cours</option>
            <option value="terminée">Terminée</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Date de maintenance <span class="text-danger">*</span></label>
          <input type="date" name="date_maintenance" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Kilométrage au moment de l'intervention</label>
          <input type="number" name="kilometrage_intervention" id="kmInput" class="form-control"
                 min="0" placeholder="Ex: 75000">
        </div>

        <div class="col-md-6">
          <label class="form-label">Coût (MAD)</label>
          <input type="number" name="cout" class="form-control" step="0.01" min="0" placeholder="Ex: 1200.00">
        </div>

        <div class="col-md-6">
          <label class="form-label">Technicien / Garage</label>
          <input type="text" name="technicien" class="form-control" placeholder="Ex: Garage Central">
        </div>

        <div class="col-12">
          <label class="form-label">Description / Observations</label>
          <textarea name="description" class="form-control" rows="3"
                    placeholder="Détails de l'intervention…"></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillKm() {
  const sel = document.getElementById('vehicleSelect');
  const opt = sel.options[sel.selectedIndex];
  const km  = opt.dataset.km;
  if (km) document.getElementById('kmInput').value = km;
}
// Pre-fill on load if vehicle pre-selected
window.addEventListener('DOMContentLoaded', fillKm);
</script>
</body>
</html>
