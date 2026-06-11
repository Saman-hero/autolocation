<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= getLanguages()[$lang]['dir'] ?? 'ltr' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= __('confirmation_reservation') ?> — AutoLocation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <style>
    body { background: #f0fdf4; display: flex; align-items: center; min-height: 100vh; }
    .confirm-box { max-width: 500px; margin: auto; background: #fff; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,.1); padding: 40px; text-align: center; }
    .check-icon { width: 72px; height: 72px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 32px; color: #fff; }
    h2 { font-weight: 800; color: #111827; margin-bottom: 4px; }
    .ref { font-size: 18px; font-weight: 700; color: var(--accent); background: #fff7ed; display: inline-block; padding: 6px 20px; border-radius: 8px; margin: 8px 0 16px; letter-spacing: .05em; }
    .detail { text-align: left; background: #f9fafb; border-radius: 12px; padding: 16px 20px; margin: 16px 0; }
    .detail .row { margin-bottom: 8px; }
    .detail .label { font-size: 12px; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
    .detail .value { font-size: 15px; font-weight: 600; color: #111827; }
    .btn-done { background: var(--primary); color: #fff; border: none; border-radius: 10px; padding: 12px 32px; font-weight: 700; text-decoration: none; display: inline-block; transition: background .2s; }
    .btn-done:hover { background: var(--primary-dark); color: #fff; }
    @media (max-width: 576px) { .confirm-box { margin: 20px; padding: 24px; } }
  </style>
</head>
<body>
<div class="confirm-box">
  <div class="check-icon">✓</div>
  <h2><?= __('reservation_effectuee') ?>!</h2>
  <div class="ref"><?= htmlspecialchars($data['reference']) ?></div>
  <div class="detail">
    <div class="row">
      <div class="col-6"><div class="label"><?= __('vehicules') ?></div><div class="value"><?= htmlspecialchars($data['vehicule']) ?></div></div>
      <div class="col-6"><div class="label"><?= __('total_estime') ?></div><div class="value"><?= number_format($data['total'], 2, ',', ' ') ?> MAD</div></div>
    </div>
    <div class="row">
      <div class="col-6"><div class="label"><?= __('date_debut') ?></div><div class="value"><?= htmlspecialchars($data['date_debut']) ?></div></div>
      <div class="col-6"><div class="label"><?= __('date_fin') ?></div><div class="value"><?= htmlspecialchars($data['date_fin']) ?></div></div>
    </div>
    <div class="row mb-0">
      <div class="col-6"><div class="label"><?= __('nom_complet') ?></div><div class="value"><?= htmlspecialchars($data['nom']) ?></div></div>
      <div class="col-6"><div class="label"><?= __('email') ?></div><div class="value"><?= htmlspecialchars($data['email']) ?></div></div>
    </div>
  </div>
  <p style="color:#6b7280;font-size:14px;margin:12px 0 20px"><?= __('reservation_ref') ?> : <?= htmlspecialchars($data['reference']) ?></p>
  <a href="/location/public/index.php?url=public" class="btn-done"><i class="fas fa-home"></i> <?= __('accueil') ?></a>
</div>
</body>
</html>
