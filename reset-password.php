<?php
require_once "config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$token   = trim($_GET['token'] ?? '');
$errors  = [];
$success = false;

if (!$token) {
    $errors[] = 'Token manquant ou invalide.';
} else {
    $stmt = $conn->prepare("
        SELECT pr.*, u.username, u.nom, u.prenom
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $errors[] = 'Ce lien est invalide, expiré ou déjà utilisé.';
    }
}

if (!$errors && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($password) < 6)     $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    if ($password !== $confirm)    $errors[] = 'Les mots de passe ne correspondent pas.';

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $reset['user_id']]);
        $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")->execute([$token]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouveau mot de passe — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body style="background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;">

<div style="width:100%;max-width:440px;padding:1rem">
  <div class="text-center mb-4">
    <div style="font-size:2.5rem;font-weight:900;color:var(--primary);letter-spacing:-.02em">AutoLocation</div>
    <div style="font-size:.8rem;color:var(--accent);font-weight:700;letter-spacing:.08em;text-transform:uppercase">Nouveau mot de passe</div>
  </div>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    <div class="mt-2"><a href="/location/forgot-password.php" class="alert-link">Demander un nouveau lien</a></div>
  </div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div class="card shadow">
    <div class="card-body p-4 text-center">
      <div style="font-size:3rem">✓</div>
      <h5 class="fw-bold mt-2">Mot de passe mis à jour !</h5>
      <p class="text-muted small">Votre mot de passe a été changé avec succès.</p>
      <a href="/location/login.php" class="btn btn-primary w-100 mt-2">Se connecter</a>
    </div>
  </div>

  <?php elseif (empty($errors) && isset($reset)): ?>
  <div class="card shadow">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-1">Nouveau mot de passe</h5>
      <p class="text-muted small mb-3">
        Pour le compte <strong><?= htmlspecialchars($reset['username']) ?></strong>
        (<?= htmlspecialchars($reset['prenom'] . ' ' . $reset['nom']) ?>)
      </p>
      <form method="POST" action="?token=<?= htmlspecialchars($token) ?>">
        <div class="mb-3">
          <label class="form-label fw-semibold">Nouveau mot de passe</label>
          <div class="input-group">
            <input type="password" name="password" id="pwd" class="form-control" placeholder="6 caractères minimum" required autofocus>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('pwd')">👁</button>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">Confirmer</label>
          <div class="input-group">
            <input type="password" name="confirm" id="confirm" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('confirm')">👁</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Enregistrer le mot de passe</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="text-center mt-3">
    <a href="/location/login.php" class="text-muted text-decoration-none small">← Retour à la connexion</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(id) {
  const f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
