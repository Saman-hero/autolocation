<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ajouter utilisateur</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>

<div class="container-fluid px-4 py-4" style="max-width:600px">

  <div class="page-header">
    <div>
      <div class="text-muted small mb-1">
        <a href="/location/public/index.php?url=users" class="text-decoration-none text-muted">Utilisateurs</a> / Ajouter
      </div>
      <h1 class="page-title">Nouvel utilisateur</h1>
    </div>
    <a href="/location/public/index.php?url=users" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <?php if ($errors): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Prénom</label>
            <input name="prenom" class="form-control" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required autofocus>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Nom</label>
            <input name="nom" class="form-control" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Identifiant (login)</label>
          <input name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="ex: jdupont" required autocomplete="off">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Rôle</label>
          <select name="role" class="form-select">
            <option value="operateur" <?= ($_POST['role'] ?? '') === 'operateur' ? 'selected' : '' ?>>Opérateur</option>
            <option value="admin"     <?= ($_POST['role'] ?? '') === 'admin'     ? 'selected' : '' ?>>Administrateur</option>
          </select>
          <div class="form-text">Les administrateurs peuvent gérer les utilisateurs.</div>
        </div>

        <hr>

        <div class="mb-3">
          <label class="form-label fw-semibold">Mot de passe</label>
          <div class="input-group">
            <input type="password" name="password" id="pwd" class="form-control"
                   placeholder="6 caractères minimum" required autocomplete="new-password">
            <button type="button" class="btn btn-outline-secondary" onclick="toggle('pwd')">👁</button>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">Confirmer le mot de passe</label>
          <div class="input-group">
            <input type="password" name="confirm" id="confirm" class="form-control" required autocomplete="new-password">
            <button type="button" class="btn btn-outline-secondary" onclick="toggle('confirm')">👁</button>
          </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
          <a href="/location/public/index.php?url=users" class="btn btn-outline-secondary">Annuler</a>
          <button class="btn btn-success px-4">Créer l'utilisateur</button>
        </div>

      </form>
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggle(id) {
  const f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
