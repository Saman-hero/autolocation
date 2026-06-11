<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier véhicule — AutoLocation</title>
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
      position: relative;
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
    }
    #imageInput {
      display: none;
    }
    .current-image-container {
      position: relative;
      display: inline-block;
    }
    .current-image-container img {
      max-width: 200px;
      max-height: 150px;
      border-radius: 8px;
      border: 2px solid #e5e7eb;
    }
    .delete-image-btn {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ef4444;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      font-size: 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .delete-image-btn:hover {
      background: #dc2626;
    }
  </style>
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
      <form method="POST" enctype="multipart/form-data" class="row g-3">

        <!-- Image Upload -->
        <div class="col-12">
          <label class="form-label fw-semibold">Photo du véhicule</label>
          <?php if (!empty($v['image'])): ?>
            <!-- Current image display -->
            <div class="current-image-container mb-2" id="currentImageContainer">
              <img src="/location/uploads/vehicles/<?= htmlspecialchars($v['image']) ?>" alt="Image actuelle">
              <button type="button" class="delete-image-btn" onclick="deleteCurrentImage()" title="Supprimer l'image">×</button>
            </div>
            <input type="hidden" name="delete_image" id="deleteImageInput" value="0">
          <?php endif; ?>
          
          <div class="image-upload-area" onclick="document.getElementById('imageInput').click()" id="uploadArea">
            <div class="upload-icon" id="uploadIcon">📸</div>
            <div class="upload-text" id="uploadText">
              <strong>Cliquez pour changer la photo</strong><br>
              ou glissez-déposez ici (JPG, PNG, max 5MB)
            </div>
            <img id="imagePreview" class="image-preview" alt="Aperçu">
          </div>
          <input type="file" name="image" id="imageInput" accept="image/*">
        </div>

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
<script>
// Image preview functionality
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');
const uploadArea = document.getElementById('uploadArea');

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
    document.getElementById('uploadIcon').style.display = 'none';
    document.getElementById('uploadText').innerHTML = '<strong>Photo sélectionnée</strong><br>Cliquez pour changer';
  };
  reader.readAsDataURL(file);
}

// Delete current image
function deleteCurrentImage() {
  if (confirm('Supprimer cette image ?')) {
    document.getElementById('deleteImageInput').value = '1';
    document.getElementById('currentImageContainer').style.display = 'none';
    document.getElementById('uploadIcon').style.display = 'block';
    document.getElementById('uploadText').innerHTML = '<strong>Cliquez pour ajouter une photo</strong><br>ou glissez-déposez ici (JPG, PNG, max 5MB)';
  }
}
</script>
</body>
</html>
