<?php
require_once "config/database.php";

// Already logged in → redirect
if (!empty($_SESSION['user_id'])) {
    header("Location: /location/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $db   = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            flash('success', 'Bienvenue, ' . $user['prenom'] . ' !');
            header("Location: /location/index.php");
            exit;
        } else {
            $error = 'Identifiant ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --primary: #1b5e35;
      --accent:  #c9943a;
    }
    body {
      background: linear-gradient(135deg, #0a2016 0%, #1b5e35 60%, #0a2016 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0,0,0,.4);
      width: 100%;
      max-width: 420px;
      overflow: hidden;
    }
    .login-header {
      background: var(--primary);
      padding: 2rem 2rem 1.5rem;
      text-align: center;
      border-bottom: 4px solid var(--accent);
    }
    .login-header img {
      height: 70px;
      margin-bottom: .75rem;
      filter: drop-shadow(0 2px 6px rgba(0,0,0,.4));
    }
    .login-header h1 {
      color: #fff;
      font-size: 1.15rem;
      font-weight: 700;
      margin: 0;
      letter-spacing: .03em;
    }
    .login-header p {
      color: var(--accent);
      font-size: .8rem;
      margin: .25rem 0 0;
      letter-spacing: .05em;
    }
    .login-body {
      padding: 2rem;
    }
    .form-label { font-weight: 600; font-size: .85rem; color: #444; }
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 .2rem rgba(27,94,53,.2);
    }
    .btn-login {
      background: var(--primary);
      color: #fff;
      border: none;
      width: 100%;
      padding: .7rem;
      font-weight: 700;
      border-radius: 8px;
      letter-spacing: .04em;
      transition: background .2s;
    }
    .btn-login:hover { background: #154d2a; color: #fff; }
    .login-footer {
      text-align: center;
      padding: .75rem;
      background: #f8f9fa;
      font-size: .75rem;
      color: #999;
      border-top: 1px solid #eee;
    }
    .input-group-text {
      background: #f5f5f5;
      border-right: none;
      color: #888;
    }
    .form-control { border-left: none; }
    .form-control:focus { border-left: none; }
    #togglePwd { cursor: pointer; background: #f5f5f5; border-left: none; color: #888; }
  </style>
</head>
<body>

<div class="login-card">

  <div class="login-header">
    <div style="font-size:3rem;margin-bottom:.5rem">🚗</div>
    <h1>AutoLocation</h1>
    <p>GESTION DE FLOTTE &amp; LOCATIONS</p>
  </div>

  <div class="login-body">

    <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3" role="alert">
      <span>⚠</span> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

      <div class="mb-3">
        <label class="form-label">Identifiant</label>
        <div class="input-group">
          <span class="input-group-text">👤</span>
          <input type="text" name="username" class="form-control"
                 placeholder="Votre identifiant"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 autofocus required>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label">Mot de passe</label>
        <div class="input-group">
          <span class="input-group-text">🔒</span>
          <input type="password" name="password" id="password" class="form-control"
                 placeholder="Votre mot de passe" required>
          <button type="button" class="btn btn-outline-secondary" id="togglePwd"
                  onclick="togglePassword()">👁</button>
        </div>
      </div>

      <button type="submit" class="btn-login">Se connecter</button>

    </form>
  </div>

  <div class="login-footer">
    Accès réservé au personnel autorisé
  </div>
</div>

<script>
function togglePassword() {
  const p = document.getElementById('password');
  p.type = p.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
