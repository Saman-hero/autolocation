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

<?php include __DIR__ . "/../../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:700px">

  <div class="page-header">
    <h1 class="page-title">🔧 Modifier la maintenance</h1>
    <a href="/location/public/index.php?url=maintenance" class="btn btn-outline-secondary">← Retour</a>
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
          <a href="/location/public/index.php?url=maintenance" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
