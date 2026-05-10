<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$record = $conn->prepare("SELECT * FROM maintenance WHERE id=?");
$record->execute([$id]);
$record = $record->fetch();
if (!$record) { header("Location: index.php"); exit; }

$vehicles = $conn->query("SELECT id, numero, marque, modele, kilometrage FROM vehicles ORDER BY numero")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId  = (int)$_POST['vehicle_id'];
    $type       = trim($_POST['type_maintenance']);
    $desc       = trim($_POST['description'] ?? '');
    $date       = $_POST['date_maintenance'];
    $km         = $_POST['kilometrage_intervention'] !== '' ? (int)$_POST['kilometrage_intervention'] : null;
    $cout       = $_POST['cout'] !== '' ? (float)$_POST['cout'] : null;
    $technicien = trim($_POST['technicien'] ?? '');
    $statut     = $_POST['statut'];
    $oldStatut  = $record['statut'];

    $conn->prepare("
        UPDATE maintenance
        SET vehicle_id=?, type_maintenance=?, description=?, date_maintenance=?,
            kilometrage_intervention=?, cout=?, technicien=?, statut=?
        WHERE id=?
    ")->execute([$vehicleId, $type, $desc, $date, $km, $cout, $technicien, $statut, $id]);

    // Mise à jour vidange si terminée
    if ($statut === 'terminée' && $km && strtolower($type) === 'vidange') {
        $conn->prepare("
            UPDATE vehicles
            SET derniere_vidange_km = ?, date_derniere_vidange = ?
            WHERE id = ?
        ")->execute([$km, $date, $vehicleId]);
    }

    // Gestion du statut véhicule
    if ($statut === 'terminée' && $oldStatut !== 'terminée') {
        $conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=? AND statut='maintenance'")
             ->execute([$vehicleId]);
    } elseif (in_array($statut, ['planifiée', 'en cours']) && $oldStatut === 'terminée') {
        $conn->prepare("UPDATE vehicles SET statut='maintenance' WHERE id=? AND statut='disponible'")
             ->execute([$vehicleId]);
    }

    flash('success', 'Maintenance mise à jour.');
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier maintenance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:700px">

  <div class="page-header">
    <h1 class="page-title">🔧 Modifier la maintenance</h1>
    <a href="index.php" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3">

        <div class="col-12">
          <label class="form-label">Véhicule <span class="text-danger">*</span></label>
          <select name="vehicle_id" class="form-select" required>
            <option value="">— Sélectionner —</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= $v['id'] ?>" <?= $v['id'] == $record['vehicle_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['numero'] . ' — ' . $v['marque'] . ' ' . $v['modele']) ?>
                (<?= number_format($v['kilometrage']) ?> km)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Type de maintenance <span class="text-danger">*</span></label>
          <select name="type_maintenance" class="form-select" required>
            <?php
            $types = ['Vidange','Révision générale','Changement de pneus','Freinage',
                      'Batterie','Climatisation','Réparation moteur','Carrosserie','Autre'];
            foreach ($types as $t):
            ?>
              <option value="<?= $t ?>" <?= $record['type_maintenance'] === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Statut</label>
          <select name="statut" class="form-select">
            <?php foreach (['planifiée','en cours','terminée'] as $s): ?>
              <option value="<?= $s ?>" <?= $record['statut'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Date de maintenance <span class="text-danger">*</span></label>
          <input type="date" name="date_maintenance" class="form-control" required
                 value="<?= htmlspecialchars($record['date_maintenance']) ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Kilométrage au moment de l'intervention</label>
          <input type="number" name="kilometrage_intervention" class="form-control" min="0"
                 value="<?= htmlspecialchars($record['kilometrage_intervention'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Coût (MAD)</label>
          <input type="number" name="cout" class="form-control" step="0.01" min="0"
                 value="<?= htmlspecialchars($record['cout'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Technicien / Garage</label>
          <input type="text" name="technicien" class="form-control"
                 value="<?= htmlspecialchars($record['technicien'] ?? '') ?>">
        </div>

        <div class="col-12">
          <label class="form-label">Description / Observations</label>
          <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($record['description'] ?? '') ?></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
