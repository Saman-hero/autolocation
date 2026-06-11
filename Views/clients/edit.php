<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier client — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:780px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1">
        <a href="/location/public/index.php?url=clients/view&id=<?= $id ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></a> / Modifier
      </div>
      <h1 class="page-title">Modifier le client</h1>
    </div>
    <a href="/location/public/index.php?url=clients/view&id=<?= $id ?>" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger mb-3">
    <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php $d = array_merge($c, $_POST); ?>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3">

        <div class="col-12"><strong class="form-section-title">Identité</strong></div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
          <input name="nom" class="form-control" required value="<?= htmlspecialchars($d['nom'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
          <input name="prenom" class="form-control" required value="<?= htmlspecialchars($d['prenom'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">CIN</label>
          <input name="cin" class="form-control" value="<?= htmlspecialchars($d['cin'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Téléphone</label>
          <input name="telephone" class="form-control" value="<?= htmlspecialchars($d['telephone'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($d['email'] ?? '') ?>">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Adresse</label>
          <textarea name="adresse" class="form-control" rows="2"><?= htmlspecialchars($d['adresse'] ?? '') ?></textarea>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Type de client</label>
          <select name="type_client" class="form-select">
            <option value="particulier" <?= ($d['type_client'] ?? '') === 'particulier' ? 'selected' : '' ?>>Particulier</option>
            <option value="entreprise"  <?= ($d['type_client'] ?? '') === 'entreprise'  ? 'selected' : '' ?>>Entreprise</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Entreprise</label>
          <input name="entreprise" class="form-control" value="<?= htmlspecialchars($d['entreprise'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Statut</label>
          <select name="statut" class="form-select">
            <option value="actif"   <?= ($d['statut'] ?? '') === 'actif'   ? 'selected' : '' ?>>Actif</option>
            <option value="inactif" <?= ($d['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
            <option value="bloqué"  <?= ($d['statut'] ?? '') === 'bloqué'  ? 'selected' : '' ?>>Bloqué</option>
          </select>
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Permis de conduire</strong></div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Numéro permis</label>
          <input name="permis_numero" class="form-control" value="<?= htmlspecialchars($d['permis_numero'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Catégorie</label>
          <select name="permis_categorie" class="form-select">
            <?php foreach (['B','A','C','D','BE','CE'] as $cat): ?>
              <option value="<?= $cat ?>" <?= ($d['permis_categorie'] ?? 'B') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Date d'expiration</label>
          <input type="date" name="permis_expiration" class="form-control" value="<?= htmlspecialchars($d['permis_expiration'] ?? '') ?>">
        </div>

        <div class="col-12"><hr class="gold-divider"></div>

        <div class="col-12">
          <label class="form-label fw-semibold">Notes</label>
          <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($d['notes'] ?? '') ?></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="/location/public/index.php?url=clients/view&id=<?= $id ?>" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-primary px-4">Enregistrer</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
