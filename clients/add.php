<?php
require_once "../config/database.php";
require_once "../models/ClientModel.php";
require_once "../includes/audit.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ClientModel($conn);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $cin    = trim($_POST['cin'] ?? '');

    if (!$nom)    $errors[] = 'Le nom est requis.';
    if (!$prenom) $errors[] = 'Le prénom est requis.';

    if (!$errors && $cin) {
        $chk = $conn->prepare("SELECT id FROM clients WHERE cin=?");
        $chk->execute([$cin]);
        if ($chk->fetch()) $errors[] = "Le CIN « $cin » est déjà enregistré.";
    }

    if (!$errors) {
        $model->create([
            ':nom'               => $nom,
            ':prenom'            => $prenom,
            ':email'             => trim($_POST['email']) ?: null,
            ':telephone'         => trim($_POST['telephone']) ?: null,
            ':adresse'           => trim($_POST['adresse']) ?: null,
            ':cin'               => $cin ?: null,
            ':permis_numero'     => trim($_POST['permis_numero']) ?: null,
            ':permis_categorie'  => $_POST['permis_categorie'] ?: 'B',
            ':permis_expiration' => $_POST['permis_expiration'] ?: null,
            ':type_client'       => $_POST['type_client'] ?: 'particulier',
            ':entreprise'        => trim($_POST['entreprise']) ?: null,
            ':statut'            => 'actif',
            ':notes'             => trim($_POST['notes']) ?: null,
        ]);
        $newId = (int)$conn->lastInsertId();
        audit_log($conn, 'CREATE', 'clients', $newId, "Client créé : $nom $prenom" . ($cin ? " (CIN: $cin)" : ''));
        flash('success', 'Client ajouté avec succès.');
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouveau client</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:700px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="index.php" class="text-decoration-none text-muted">Clients</a> / Ajouter</div>
      <h1 class="page-title">Nouveau client</h1>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">← Retour</a>
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
                     <?= ($_POST['type_client'] ?? 'particulier') === 'particulier' ? 'checked' : '' ?>
                     onchange="toggleEntreprise()">
              <label class="form-check-label" for="tc_part">Particulier</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type_client" id="tc_ent" value="entreprise"
                     <?= ($_POST['type_client'] ?? '') === 'entreprise' ? 'checked' : '' ?>
                     onchange="toggleEntreprise()">
              <label class="form-check-label" for="tc_ent">Entreprise</label>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
          <input name="nom" class="form-control" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
          <input name="prenom" class="form-control" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
        </div>

        <div class="col-12" id="entreprise_row" style="display:none">
          <label class="form-label fw-semibold">Raison sociale</label>
          <input name="entreprise" class="form-control" placeholder="Nom de l'entreprise" value="<?= htmlspecialchars($_POST['entreprise'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">CIN / Passeport</label>
          <input name="cin" id="cinField" class="form-control" placeholder="Ex: BE123456" value="<?= htmlspecialchars($_POST['cin'] ?? '') ?>">
          <div id="cinWarning" class="duplicate-warn" style="display:none"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Téléphone</label>
          <input name="telephone" class="form-control" placeholder="06XXXXXXXX" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" name="email" id="emailField" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          <div id="emailWarning" class="duplicate-warn" style="display:none"></div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Adresse</label>
          <textarea name="adresse" class="form-control" rows="2"><?= htmlspecialchars($_POST['adresse'] ?? '') ?></textarea>
        </div>

        <div class="col-12"><hr class="gold-divider"><strong class="form-section-title">Permis de conduire</strong></div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Numéro de permis</label>
          <input name="permis_numero" class="form-control" value="<?= htmlspecialchars($_POST['permis_numero'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Catégorie permis</label>
          <select name="permis_categorie" class="form-select">
            <?php foreach (['B','BE','C','CE','D','A'] as $cat): ?>
              <option <?= ($_POST['permis_categorie'] ?? 'B') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Date d'expiration</label>
          <input type="date" name="permis_expiration" class="form-control" value="<?= htmlspecialchars($_POST['permis_expiration'] ?? '') ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Notes internes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Observations…"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-success px-4">Créer le client</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
.duplicate-warn {
  background: #fef3c7; color: #92400e; border-left: 3px solid #f59e0b;
  padding: 6px 10px; border-radius: 0 5px 5px 0; font-size: .82rem; margin-top: 4px;
  animation: fadeSlideIn .25s ease;
}
@keyframes fadeSlideIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
</style>
<script>
function toggleEntreprise() {
  const isEnt = document.getElementById('tc_ent').checked;
  document.getElementById('entreprise_row').style.display = isEnt ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleEntreprise);

function checkDuplicate(field, type, warnEl) {
  const val = field.value.trim();
  if (!val) { warnEl.style.display = 'none'; return; }
  fetch('/location/api/check-duplicate.php?' + type + '=' + encodeURIComponent(val))
    .then(r => r.json())
    .then(data => {
      if (data.exists) {
        warnEl.innerHTML = '⚠ Ce ' + (type === 'cin' ? 'CIN' : 'email') + ' est déjà utilisé par : <strong>'
          + data.client.prenom + ' ' + data.client.nom + '</strong>'
          + ' <a href="/location/clients/view.php?id=' + data.client.id + '" target="_blank" class="ms-1">Voir fiche</a>';
        warnEl.style.display = 'block';
      } else {
        warnEl.style.display = 'none';
      }
    })
    .catch(() => { warnEl.style.display = 'none'; });
}

document.getElementById('cinField').addEventListener('blur', function() {
  checkDuplicate(this, 'cin', document.getElementById('cinWarning'));
});
document.getElementById('emailField').addEventListener('blur', function() {
  checkDuplicate(this, 'email', document.getElementById('emailWarning'));
});
</script>
</body>
</html>
