<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ajouter véhicule — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <style>
    .image-upload-area {
      border: 2px dashed #d1d5db;
      border-radius: 12px;
      padding: 30px 20px;
      text-align: center;
      cursor: pointer;
      transition: all .2s;
      background: #f9fafb;
    }
    .image-upload-area:hover {
      border-color: #3b82f6;
      background: #f0f7ff;
    }
    .image-upload-area.dragover {
      border-color: #3b82f6;
      background: #eff6ff;
    }
    .image-upload-area .upload-icon {
      font-size: 48px;
      color: #9ca3af;
      margin-bottom: 10px;
    }
    .image-upload-area .upload-text {
      color: #6b7280;
      font-size: 14px;
    }
    .image-upload-area .upload-text strong {
      color: #3b82f6;
    }
    .image-preview {
      max-width: 200px;
      max-height: 150px;
      border-radius: 8px;
      margin-top: 10px;
      display: none;
    }
    #imageInput {
      display: none;
    }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:780px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="/location/public/index.php?url=vehicles" class="text-decoration-none text-muted">Véhicules</a> / Ajouter</div>
      <h1 class="page-title">Nouveau véhicule</h1>
    </div>
    <a href="/location/public/index.php?url=vehicles" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data" class="row g-3">

        <!-- Image Upload -->
        <div class="col-12">
          <label class="form-label fw-semibold">Photo du véhicule</label>
          <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
            <div class="upload-icon">📸</div>
            <div class="upload-text">
              <strong>Cliquez pour ajouter une photo</strong><br>
              ou glissez-déposez ici (JPG, PNG, max 5MB)
            </div>
            <img id="imagePreview" class="image-preview" alt="Aperçu">
          </div>
          <input type="file" name="image" id="imageInput" accept="image/*">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Numéro interne <span class="text-danger">*</span></label>
          <input name="numero" class="form-control" placeholder="Ex: VH-006" required value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Immatriculation</label>
          <input name="immatriculation" class="form-control" value="<?= htmlspecialchars($_POST['immatriculation'] ?? '') ?>">
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
          <input name="couleur" class="form-control" value="<?= htmlspecialchars($_POST['couleur'] ?? '') ?>">
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

        <div class="col-md-3">
          <label class="form-label fw-semibold">Type de vidange</label>
          <select name="type_vidange" class="form-select">
            <option value="">— Non défini —</option>
            <?php foreach (['Huile moteur 10W-40','Huile moteur 5W-30','Huile diesel','Vidange complète'] as $tv): ?>
              <option><?= $tv ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Intervalle vidange (km)</label>
          <select name="intervalle_vidange" class="form-select">
            <?php foreach ([5000=>5000,7000=>7000,10000=>10000,15000=>15000] as $iv): ?>
              <option value="<?= $iv ?>" <?= $iv === 10000 ? 'selected' : '' ?>><?= number_format($iv) ?> km</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Km dernière vidange</label>
          <input type="number" name="derniere_vidange_km" class="form-control" min="0">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Date dernière vidange</label>
          <input type="date" name="date_derniere_vidange" class="form-control">
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="/location/public/index.php?url=vehicles" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-success px-4">Ajouter le véhicule</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Image preview functionality
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');
const uploadArea = document.querySelector('.image-upload-area');

// Handle file selection
imageInput.addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    showPreview(file);
  }
});

// Handle drag and drop
uploadArea.addEventListener('dragover', function(e) {
  e.preventDefault();
  uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', function(e) {
  e.preventDefault();
  uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', function(e) {
  e.preventDefault();
  uploadArea.classList.remove('dragover');
  const file = e.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) {
    imageInput.files = e.dataTransfer.files;
    showPreview(file);
  }
});

function showPreview(file) {
  const reader = new FileReader();
  reader.onload = function(e) {
    imagePreview.src = e.target.result;
    imagePreview.style.display = 'block';
    document.querySelector('.upload-icon').style.display = 'none';
    document.querySelector('.upload-text').innerHTML = '<strong>Photo sélectionnée</strong><br>Cliquez pour changer';
  };
  reader.readAsDataURL(file);
}
</script>
</body>
</html>
