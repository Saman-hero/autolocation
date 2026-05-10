<?php
require_once "../config/database.php";
require_once "../models/VehicleModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new VehicleModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $model->create([
        ':numero'               => trim($_POST['numero']),
        ':immatriculation'      => trim($_POST['immatriculation']) ?: null,
        ':marque'               => trim($_POST['marque']),
        ':modele'               => trim($_POST['modele']),
        ':annee'                => (int)$_POST['annee'],
        ':couleur'              => trim($_POST['couleur']) ?: null,
        ':nb_places'            => (int)($_POST['nb_places'] ?: 5),
        ':categorie'            => $_POST['categorie'],
        ':kilometrage'          => (int)($_POST['kilometrage'] ?: 0),
        ':statut'               => $_POST['statut'],
        ':prix_jour'            => (float)($_POST['prix_jour'] ?: 0),
        ':caution'              => (float)($_POST['caution'] ?: 0),
        ':type_vidange'         => $_POST['type_vidange'] ?: null,
        ':intervalle_vidange'   => (int)($_POST['intervalle_vidange'] ?: 10000),
        ':derniere_vidange_km'  => $_POST['derniere_vidange_km'] !== '' ? (int)$_POST['derniere_vidange_km'] : null,
        ':date_derniere_vidange'=> $_POST['date_derniere_vidange'] ?: null,
    ]);
    flash('success', 'Véhicule ajouté avec succès.');
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ajouter véhicule</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:780px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="index.php" class="text-decoration-none text-muted">Véhicules</a> / Ajouter</div>
      <h1 class="page-title">Nouveau véhicule</h1>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3">

        <div class="col-md-4">
          <label class="form-label fw-semibold">Numéro interne <span class="text-danger">*</span></label>
          <input name="numero" class="form-control" placeholder="Ex: VH-006" required value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Immatriculation</label>
          <input name="immatriculation" class="form-control" placeholder="Ex: 12345-A-1" value="<?= htmlspecialchars($_POST['immatriculation'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Catégorie <span class="text-danger">*</span></label>
          <select name="categorie" class="form-select" required>
            <?php foreach (['économique','berline','SUV','premium','utilitaire'] as $cat): ?>
              <option value="<?= $cat ?>" <?= ($_POST['categorie'] ?? '') === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Marque <span class="text-danger">*</span></label>
          <input name="marque" class="form-control" required value="<?= htmlspecialchars($_POST['marque'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Modèle <span class="text-danger">*</span></label>
          <input name="modele" class="form-control" required value="<?= htmlspecialchars($_POST['modele'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Année</label>
          <input type="number" name="annee" class="form-control" min="1990" max="2030" value="<?= htmlspecialchars($_POST['annee'] ?? date('Y')) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Places</label>
          <input type="number" name="nb_places" class="form-control" min="1" max="50" value="<?= htmlspecialchars($_POST['nb_places'] ?? '5') ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Couleur</label>
          <input name="couleur" class="form-control" placeholder="Ex: Blanc" value="<?= htmlspecialchars($_POST['couleur'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Kilométrage</label>
          <input type="number" name="kilometrage" class="form-control" min="0" value="<?= htmlspecialchars($_POST['kilometrage'] ?? '0') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Statut</label>
          <select name="statut" class="form-select">
            <option value="disponible">Disponible</option>
            <option value="maintenance">Maintenance</option>
            <option value="indisponible">Indisponible</option>
          </select>
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Tarification</strong></div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Prix par jour (MAD) <span class="text-danger">*</span></label>
          <input type="number" name="prix_jour" class="form-control" step="0.01" min="0" required value="<?= htmlspecialchars($_POST['prix_jour'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Caution (MAD)</label>
          <input type="number" name="caution" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($_POST['caution'] ?? '0') ?>">
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Entretien / Vidange</strong></div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Type de vidange</label>
          <select name="type_vidange" class="form-select">
            <option value="">— Non défini —</option>
            <option value="Huile moteur 10W-40">Huile moteur 10W-40</option>
            <option value="Huile moteur 5W-30">Huile moteur 5W-30</option>
            <option value="Huile diesel">Huile diesel</option>
            <option value="Vidange complète">Vidange complète</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Intervalle vidange (km)</label>
          <select name="intervalle_vidange" class="form-select">
            <option value="5000">5 000 km</option>
            <option value="7000">7 000 km</option>
            <option value="10000" selected>10 000 km</option>
            <option value="15000">15 000 km</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Km dernière vidange</label>
          <input type="number" name="derniere_vidange_km" class="form-control" min="0" value="">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Date dernière vidange</label>
          <input type="date" name="date_derniere_vidange" class="form-control">
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-success px-4">Ajouter le véhicule</button>
        </div>

      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
