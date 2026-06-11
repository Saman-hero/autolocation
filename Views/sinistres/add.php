<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Déclarer un sinistre</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:680px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="/location/public/index.php?url=sinistres" class="text-decoration-none text-muted">Sinistres</a> / Nouveau</div>
      <h1 class="page-title">Déclarer un sinistre</h1>
    </div>
    <a href="javascript:history.back()" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <?php if ($errors): ?>
  <div class="alert alert-danger mb-3">
    <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3">

        <div class="col-md-6">
          <label class="form-label fw-semibold">Référence sinistre</label>
          <input name="reference" class="form-control" value="<?= htmlspecialchars($_POST['reference'] ?? $autoRef) ?>">
          <div class="form-text">Générée automatiquement.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Date du sinistre</label>
          <input type="date" name="date_sinistre" class="form-control" value="<?= htmlspecialchars($_POST['date_sinistre'] ?? date('Y-m-d')) ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Véhicule <span class="text-danger">*</span></label>
          <select name="vehicle_id" class="form-select" required>
            <option value="">— Sélectionner —</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= $v['id'] ?>" <?= (($_POST['vehicle_id'] ?? $preVehicleId) == $v['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['numero'] . ' — ' . $v['marque'] . ' ' . $v['modele']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Client concerné</label>
          <select name="client_id" class="form-select">
            <option value="">— Sans client —</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (($_POST['client_id'] ?? $preClientId) == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <input type="hidden" name="reservation_id" value="<?= $preResId ?>">

        <div class="col-md-6">
          <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
          <select name="type" class="form-select" required>
            <option value="">— Sélectionner —</option>
            <?php foreach (['accident','dommage','vol','panne','autre'] as $t): ?>
              <option value="<?= $t ?>" <?= ($_POST['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Statut</label>
          <select name="statut" class="form-select">
            <option value="ouvert"   <?= ($_POST['statut'] ?? 'ouvert') === 'ouvert'   ? 'selected' : '' ?>>Ouvert</option>
            <option value="en cours" <?= ($_POST['statut'] ?? '') === 'en cours' ? 'selected' : '' ?>>En cours</option>
            <option value="clôturé"  <?= ($_POST['statut'] ?? '') === 'clôturé'  ? 'selected' : '' ?>>Clôturé</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Décrivez le sinistre…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Coût de réparation (MAD)</label>
          <input type="number" name="cout_reparation" class="form-control" step="0.01" min="0"
                 value="<?= htmlspecialchars($_POST['cout_reparation'] ?? '') ?>" placeholder="Ex: 5000.00">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Prise en charge</label>
          <select name="prise_en_charge" class="form-select">
            <option value="client"    <?= ($_POST['prise_en_charge'] ?? 'client')    === 'client'    ? 'selected' : '' ?>>Client</option>
            <option value="assurance" <?= ($_POST['prise_en_charge'] ?? '') === 'assurance' ? 'selected' : '' ?>>Assurance</option>
            <option value="société"   <?= ($_POST['prise_en_charge'] ?? '') === 'société'   ? 'selected' : '' ?>>Société</option>
          </select>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="javascript:history.back()" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-danger px-4">Déclarer le sinistre</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
