<?php
require_once "../config/database.php";
require_once "../models/ClientModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ClientModel($conn);
$errors = [];

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }
$c = $model->getById($id);
if (!$c) { header("Location: index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $cin    = trim($_POST['cin'] ?? '');

    if (!$nom)    $errors[] = 'Le nom est requis.';
    if (!$prenom) $errors[] = 'Le prénom est requis.';

    if (!$errors && $cin) {
        $chk = $conn->prepare("SELECT id FROM clients WHERE cin=? AND id!=?");
        $chk->execute([$cin, $id]);
        if ($chk->fetch()) $errors[] = "Le CIN « $cin » est déjà utilisé par un autre client.";
    }

    if (!$errors) {
        $model->update([
            ':id'               => $id,
            ':nom'              => $nom,
            ':prenom'           => $prenom,
            ':email'            => trim($_POST['email']) ?: null,
            ':telephone'        => trim($_POST['telephone']) ?: null,
            ':adresse'          => trim($_POST['adresse']) ?: null,
            ':cin'              => $cin ?: null,
            ':permis_numero'    => trim($_POST['permis_numero']) ?: null,
            ':permis_categorie' => $_POST['permis_categorie'] ?: 'B',
            ':permis_expiration'=> $_POST['permis_expiration'] ?: null,
            ':type_client'      => $_POST['type_client'] ?: 'particulier',
            ':entreprise'       => trim($_POST['entreprise']) ?: null,
            ':statut'           => $_POST['statut'],
            ':notes'            => trim($_POST['notes']) ?: null,
        ]);
        flash('success', 'Client mis à jour.');
        header("Location: view.php?id=$id");
        exit;
    }
}

$d = array_merge($c, $_POST);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier client</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:700px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="index.php" class="text-decoration-none text-muted">Clients</a> / Modifier</div>
      <h1 class="page-title"><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></h1>
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

        <div class="col-12">
          <label class="form-label fw-semibold">Type de client</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type_client" id="tc_part" value="particulier"
                     <?= ($d['type_client'] ?? 'particulier') === 'particulier' ? 'checked' : '' ?>
                     onchange="toggleEntreprise()">
              <label class="form-check-label" for="tc_part">Particulier</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type_client" id="tc_ent" value="entreprise"
                     <?= ($d['type_client'] ?? '') === 'entreprise' ? 'checked' : '' ?>
                     onchange="toggleEntreprise()">
              <label class="form-check-label" for="tc_ent">Entreprise</label>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
          <input name="nom" class="form-control" required value="<?= htmlspecialchars($d['nom'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
          <input name="prenom" class="form-control" required value="<?= htmlspecialchars($d['prenom'] ?? '') ?>">
        </div>

        <div class="col-12" id="entreprise_row">
          <label class="form-label fw-semibold">Raison sociale</label>
          <input name="entreprise" class="form-control" value="<?= htmlspecialchars($d['entreprise'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">CIN / Passeport</label>
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
        <div class="col-md-6">
          <label class="form-label fw-semibold">Statut</label>
          <select name="statut" class="form-select">
            <option value="actif"       <?= ($d['statut'] ?? '') === 'actif'       ? 'selected' : '' ?>>Actif</option>
            <option value="suspendu"    <?= ($d['statut'] ?? '') === 'suspendu'    ? 'selected' : '' ?>>Suspendu</option>
            <option value="liste_noire" <?= ($d['statut'] ?? '') === 'liste_noire' ? 'selected' : '' ?>>Liste noire</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Adresse</label>
          <textarea name="adresse" class="form-control" rows="2"><?= htmlspecialchars($d['adresse'] ?? '') ?></textarea>
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Permis de conduire</strong></div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Numéro de permis</label>
          <input name="permis_numero" class="form-control" value="<?= htmlspecialchars($d['permis_numero'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Catégorie</label>
          <select name="permis_categorie" class="form-select">
            <?php foreach (['B','BE','C','CE','D','A'] as $cat): ?>
              <option <?= ($d['permis_categorie'] ?? 'B') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Date d'expiration</label>
          <input type="date" name="permis_expiration" class="form-control" value="<?= htmlspecialchars($d['permis_expiration'] ?? '') ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Notes internes</label>
          <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($d['notes'] ?? '') ?></textarea>
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
function toggleEntreprise() {
  const isEnt = document.getElementById('tc_ent').checked;
  document.getElementById('entreprise_row').style.display = isEnt ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleEntreprise);
</script>
</body>
</html>
