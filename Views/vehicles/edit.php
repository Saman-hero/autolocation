<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier véhicule — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:780px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="/location/public/index.php?url=vehicles" class="text-decoration-none text-muted">Véhicules</a> / Modifier</div>
      <h1 class="page-title"><?= htmlspecialchars($v['numero']) ?></h1>
    </div>
    <a href="/location/public/index.php?url=vehicles" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <?php if ($kmSince >= $intervalle): ?>
  <div class="alert alert-danger mb-3">⚠ Vidange dépassée — <?= number_format($kmSince) ?> km depuis la dernière vidange.</div>
  <?php elseif ($kmSince >= $intervalle * 0.8): ?>
  <div class="alert alert-warning mb-3">⚡ Vidange bientôt nécessaire — <?= number_format($intervalle - $kmSince) ?> km restants.</div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3">

        <div class="col-md-4">
          <label class="form-label fw-semibold">Numéro interne <span class="text-danger">*</span></label>
          <input name="numero" class="form-control" required value="<?= htmlspecialchars($v['numero']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Immatriculation</label>
          <input name="immatriculation" class="form-control" value="<?= htmlspecialchars($v['immatriculation'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Catégorie</label>
          <select name="categorie" class="form-select">
            <?php foreach (['économique','berline','SUV','premium','utilitaire'] as $cat): ?>
              <option value="<?= $cat ?>" <?= $v['categorie'] === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Marque</label>
          <input name="marque" class="form-control" value="<?= htmlspecialchars($v['marque'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Modèle</label>
          <input name="modele" class="form-control" value="<?= htmlspecialchars($v['modele'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Année</label>
          <input type="number" name="annee" class="form-control" value="<?= $v['annee'] ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Places</label>
          <input type="number" name="nb_places" class="form-control" min="1" value="<?= $v['nb_places'] ?? 5 ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Couleur</label>
          <input name="couleur" class="form-control" value="<?= htmlspecialchars($v['couleur'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Kilométrage</label>
          <input type="number" name="kilometrage" class="form-control" min="0" value="<?= $v['kilometrage'] ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Statut</label>
          <select name="statut" class="form-select">
            <?php foreach (['disponible','loué','maintenance','indisponible'] as $s): ?>
              <option value="<?= $s ?>" <?= $v['statut'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Tarification</strong></div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Prix par jour (MAD)</label>
          <input type="number" name="prix_jour" class="form-control" step="0.01" min="0" value="<?= $v['prix_jour'] ?? 0 ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Caution (MAD)</label>
          <input type="number" name="caution" class="form-control" step="0.01" min="0" value="<?= $v['caution'] ?? 0 ?>">
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Entretien / Vidange</strong></div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Type de vidange</label>
          <select name="type_vidange" class="form-select">
            <option value="">— Non défini —</option>
            <?php foreach (['Huile moteur 10W-40','Huile moteur 5W-30','Huile diesel','Vidange complète'] as $tv): ?>
              <option value="<?= $tv ?>" <?= ($v['type_vidange'] ?? '') === $tv ? 'selected' : '' ?>><?= $tv ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Intervalle (km)</label>
          <select name="intervalle_vidange" class="form-select">
            <?php foreach ([5000,7000,10000,15000] as $iv): ?>
              <option value="<?= $iv ?>" <?= ($v['intervalle_vidange'] ?? 10000) == $iv ? 'selected' : '' ?>><?= number_format($iv) ?> km</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Km dernière vidange</label>
          <input type="number" name="derniere_vidange_km" class="form-control" min="0" value="<?= $v['derniere_vidange_km'] ?? '' ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Date dernière vidange</label>
          <input type="date" name="date_derniere_vidange" class="form-control" value="<?= $v['date_derniere_vidange'] ?? '' ?>">
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="/location/public/index.php?url=vehicles" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-primary px-4">Enregistrer</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
