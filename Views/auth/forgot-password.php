<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mot de passe oublié — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body style="background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;">

<div style="width:100%;max-width:440px;padding:1rem">
  <div class="text-center mb-4">
    <div style="font-size:2.5rem;font-weight:900;color:var(--primary);letter-spacing:-.02em">AutoLocation</div>
    <div style="font-size:.8rem;color:var(--accent);font-weight:700;letter-spacing:.08em;text-transform:uppercase">Réinitialisation du mot de passe</div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?> mb-3"><?= $msg ?></div>
  <?php endif; ?>

  <?php if ($resetLink): ?>
  <div class="card mb-3 border-warning">
    <div class="card-body">
      <div class="fw-semibold mb-2 text-warning">Lien de réinitialisation (usage administrateur) :</div>
      <div class="font-monospace small bg-light p-2 rounded text-break" style="word-break:break-all">
        <a href="<?= htmlspecialchars($resetLink) ?>"><?= htmlspecialchars($resetLink) ?></a>
      </div>
      <div class="text-muted small mt-2">Transmettez ce lien à l'utilisateur. Il expire dans 1 heure.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card shadow">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-3">Mot de passe oublié ?</h5>
      <p class="text-muted small mb-3">Saisissez votre identifiant pour générer un lien de réinitialisation.</p>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-semibold">Identifiant</label>
          <input type="text" name="username" class="form-control" placeholder="Votre identifiant de connexion"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100">Générer le lien</button>
      </form>
    </div>
  </div>

  <div class="text-center mt-3">
    <a href="/location/public/index.php?url=login" class="text-muted text-decoration-none small">← Retour à la connexion</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
