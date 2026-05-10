<?php
require_once "../config/database.php";
require_once "../models/ClientModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ClientModel($conn);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }
$c = $model->getById($id);
if (!$c) { header("Location: index.php"); exit; }

// Historique des réservations
$reservations = $conn->prepare("
    SELECT r.*, v.marque, v.modele, v.numero
    FROM reservations r
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE r.client_id = ?
    ORDER BY r.created_at DESC
");
$reservations->execute([$id]);
$reservations = $reservations->fetchAll();

$totalLocations = count($reservations);
$totalCA        = array_sum(array_column($reservations, 'montant_total'));
$enCours        = count(array_filter($reservations, fn($r) => $r['statut'] === 'en cours'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <div>
      <div class="text-muted small mb-1"><a href="index.php" class="text-decoration-none text-muted">Clients</a> / Profil</div>
      <h1 class="page-title"><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></h1>
    </div>
    <div class="d-flex gap-2">
      <a href="index.php" class="btn btn-outline-secondary">← Retour</a>
      <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">Modifier</a>
      <a href="/location/reservations/add.php?client_id=<?= $id ?>" class="btn btn-success">+ Nouvelle location</a>
    </div>
  </div>

  <div class="row g-3">

    <!-- LEFT : Infos client -->
    <div class="col-lg-4">

      <!-- Statut badge -->
      <div class="card mb-3">
        <div class="card-body text-center py-4">
          <?php
            $sBadge = ['actif' => 'badge-disponible', 'suspendu' => 'badge-conge', 'liste_noire' => 'badge-annulee'];
            $sLabel = ['actif' => 'Actif', 'suspendu' => 'Suspendu', 'liste_noire' => 'Liste noire'];
          ?>
          <span class="badge <?= $sBadge[$c['statut']] ?? 'bg-secondary' ?> fs-6 px-3 py-2">
            <?= $sLabel[$c['statut']] ?? $c['statut'] ?>
          </span>
          <div class="mt-2 text-muted small">
            <?= $c['type_client'] === 'entreprise' ? '🏢 Entreprise' : '👤 Particulier' ?>
            <?php if ($c['entreprise']): ?> — <?= htmlspecialchars($c['entreprise']) ?><?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Infos -->
      <div class="card mb-3">
        <div class="card-header">Informations</div>
        <div class="card-body">
          <?php $rows = [
            'CIN / Passeport' => $c['cin'],
            'Téléphone'        => $c['telephone'],
            'Email'            => $c['email'],
            'Adresse'          => $c['adresse'],
            'Client depuis'    => $c['created_at'] ? date('d/m/Y', strtotime($c['created_at'])) : '—',
          ]; ?>
          <?php foreach ($rows as $label => $val): ?>
          <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.875rem">
            <span class="text-muted"><?= $label ?></span>
            <span class="fw-semibold text-end"><?= htmlspecialchars($val ?: '—') ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Permis -->
      <div class="card mb-3">
        <div class="card-header">Permis de conduire</div>
        <div class="card-body">
          <?php
            $expDate = $c['permis_expiration'] ? new DateTime($c['permis_expiration']) : null;
            $isExp   = $expDate && $expDate < new DateTime();
            $isSoon  = $expDate && !$isExp && $expDate < new DateTime('+30 days');
          ?>
          <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.875rem">
            <span class="text-muted">Numéro</span>
            <span class="fw-semibold"><?= htmlspecialchars($c['permis_numero'] ?: '—') ?></span>
          </div>
          <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.875rem">
            <span class="text-muted">Catégorie</span>
            <span class="fw-semibold"><?= htmlspecialchars($c['permis_categorie'] ?: '—') ?></span>
          </div>
          <div class="d-flex justify-content-between py-2" style="font-size:.875rem">
            <span class="text-muted">Expiration</span>
            <span>
              <?= $c['permis_expiration'] ? date('d/m/Y', strtotime($c['permis_expiration'])) : '—' ?>
              <?php if ($isExp): ?>
                <span class="badge bg-danger ms-1">Expiré</span>
              <?php elseif ($isSoon): ?>
                <span class="badge bg-warning text-dark ms-1">Bientôt</span>
              <?php endif; ?>
            </span>
          </div>
        </div>
      </div>

      <?php if ($c['notes']): ?>
      <div class="card">
        <div class="card-header">Notes internes</div>
        <div class="card-body text-muted small"><?= nl2br(htmlspecialchars($c['notes'])) ?></div>
      </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT : Stats + Historique -->
    <div class="col-lg-8">

      <!-- Stats -->
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="stat-card stat-blue">
            <div class="stat-number"><?= $totalLocations ?></div>
            <div class="stat-label">Locations au total</div>
            <div class="stat-bg-icon">📋</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card stat-orange">
            <div class="stat-number"><?= $enCours ?></div>
            <div class="stat-label">En cours</div>
            <div class="stat-bg-icon">🔑</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card stat-green">
            <div class="stat-number"><?= number_format($totalCA, 0) ?></div>
            <div class="stat-label">CA total (MAD)</div>
            <div class="stat-bg-icon">💰</div>
          </div>
        </div>
      </div>

      <!-- Historique réservations -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Historique des locations (<?= $totalLocations ?>)</span>
          <a href="/location/reservations/add.php?client_id=<?= $id ?>" class="btn btn-sm btn-success">+ Nouvelle</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($reservations)): ?>
            <div class="empty-state py-3">
              <span class="empty-icon">📋</span>
              <p>Aucune location pour ce client</p>
            </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Référence</th>
                  <th>Véhicule</th>
                  <th>Début</th>
                  <th>Fin prévue</th>
                  <th>Total</th>
                  <th>Statut</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($reservations as $r): ?>
                <?php
                  $rBadge = [
                    'en attente' => 'bg-secondary',
                    'confirmée'  => 'bg-primary',
                    'en cours'   => 'badge-encours',
                    'terminée'   => 'badge-terminee',
                    'annulée'    => 'badge-annulee',
                  ];
                ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($r['reference']) ?></td>
                  <td>
                    <strong><?= htmlspecialchars($r['numero']) ?></strong>
                    <div class="text-muted small"><?= htmlspecialchars($r['marque'] . ' ' . $r['modele']) ?></div>
                  </td>
                  <td class="text-muted small"><?= date('d/m/Y', strtotime($r['date_debut'])) ?></td>
                  <td class="text-muted small"><?= date('d/m/Y', strtotime($r['date_fin_prevue'])) ?></td>
                  <td class="fw-semibold"><?= $r['montant_total'] ? number_format($r['montant_total'], 2) . ' MAD' : '—' ?></td>
                  <td><span class="badge <?= $rBadge[$r['statut']] ?? 'bg-secondary' ?>"><?= ucfirst($r['statut']) ?></span></td>
                  <td><a href="/location/reservations/view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
