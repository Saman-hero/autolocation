<?php
require_once "../config/database.php";
require_once "../includes/audit.php";

$db   = new Database();
$conn = $db->getConnection();

$reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);
$type          = in_array($_GET['type'] ?? $_POST['type'] ?? '', ['depart','retour']) ? ($_GET['type'] ?? $_POST['type']) : 'depart';

if (!$reservationId) { header("Location: index.php"); exit; }

$res = $conn->prepare("
    SELECT r.*, v.numero, v.marque, v.modele, v.kilometrage,
           c.nom AS client_nom, c.prenom AS client_prenom
    FROM reservations r
    JOIN vehicles v ON r.vehicle_id = v.id
    JOIN clients c ON r.client_id = c.id
    WHERE r.id = ?
");
$res->execute([$reservationId]);
$res = $res->fetch();
if (!$res) { flash('danger', 'Réservation introuvable.'); header("Location: index.php"); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carburant = (int)($_POST['carburant'] ?? 4);
    $km        = $_POST['km'] !== '' ? (int)$_POST['km'] : null;
    $proprete  = in_array($_POST['proprete'] ?? '', ['propre','moyen','sale']) ? $_POST['proprete'] : 'propre';
    $rayures   = isset($_POST['rayures']) ? 1 : 0;
    $dommages  = trim($_POST['dommages'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    $stmt = $conn->prepare("
        INSERT INTO etat_vehicule (reservation_id, vehicle_id, type, carburant, km, proprete, rayures, dommages, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $reservationId, $res['vehicle_id'], $type,
        $carburant, $km, $proprete, $rayures, $dommages ?: null, $notes ?: null,
        $_SESSION['user_id'] ?? null,
    ]);
    $newId = (int)$conn->lastInsertId();

    audit_log($conn, 'CREATE', 'etat_vehicule', $newId,
        "État véhicule enregistré ($type) pour réservation {$res['reference']} — {$res['numero']}");

    flash('success', "État du véhicule ($type) enregistré.");
    header("Location: /location/reservations/view.php?id=$reservationId");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>État du véhicule — <?= ucfirst($type) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <style>
    .fuel-gauge { display: flex; gap: 6px; margin-top: 8px; }
    .fuel-seg { width: 40px; height: 26px; border-radius: 4px; border: 2px solid #d1dbe6; cursor: pointer; transition: all .15s; display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; color: #888; }
    .fuel-seg:hover { border-color: var(--accent); }
    .fuel-seg.active { border-color: var(--primary); color: #fff; }
    .fuel-seg.active[data-v="0"] { background: #dc2626; border-color: #dc2626; }
    .fuel-seg.active[data-v="1"], .fuel-seg.active[data-v="2"] { background: #f59e0b; border-color: #f59e0b; }
    .fuel-seg.active[data-v="3"], .fuel-seg.active[data-v="4"] { background: #f97316; border-color: #f97316; }
    .fuel-seg.active[data-v="5"], .fuel-seg.active[data-v="6"] { background: #22c55e; border-color: #22c55e; }
    .fuel-seg.active[data-v="7"], .fuel-seg.active[data-v="8"] { background: #16a34a; border-color: #16a34a; }
    .fuel-labels { display: flex; justify-content: space-between; font-size: .72rem; color: #888; margin-top: 3px; width: calc(40px*9 + 6px*8); }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:640px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1">
        <a href="/location/reservations/view.php?id=<?= $reservationId ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($res['reference']) ?></a> / État véhicule
      </div>
      <h1 class="page-title">
        <?= $type === 'depart' ? '🚗 État au départ' : '🏁 État au retour' ?>
      </h1>
    </div>
    <a href="/location/reservations/view.php?id=<?= $reservationId ?>" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <!-- Info réservation -->
  <div class="alert alert-info mb-3 d-flex gap-3 align-items-center">
    <div>
      <strong><?= htmlspecialchars($res['numero'] . ' — ' . $res['marque'] . ' ' . $res['modele']) ?></strong><br>
      <small>Client : <?= htmlspecialchars($res['client_nom'] . ' ' . $res['client_prenom']) ?> · Réf. <?= htmlspecialchars($res['reference']) ?></small>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-4">
        <input type="hidden" name="reservation_id" value="<?= $reservationId ?>">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

        <!-- Carburant -->
        <div class="col-12">
          <label class="form-label fw-semibold">Niveau de carburant</label>
          <div class="fuel-gauge" id="fuelGauge">
            <?php
            $fuelLabels = ['0','1/8','1/4','3/8','1/2','5/8','3/4','7/8','Plein'];
            for ($i = 0; $i <= 8; $i++):
            ?>
            <div class="fuel-seg <?= $i === 4 ? 'active' : '' ?>" data-v="<?= $i ?>" onclick="setFuel(<?= $i ?>)">
              <?= $fuelLabels[$i] ?>
            </div>
            <?php endfor; ?>
          </div>
          <div class="fuel-labels"><span>Vide</span><span>Plein</span></div>
          <input type="hidden" name="carburant" id="carburantInput" value="4">
        </div>

        <!-- Kilométrage -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kilométrage</label>
          <input type="number" name="km" class="form-control"
                 placeholder="<?= $res['kilometrage'] ? 'Actuel : ' . number_format($res['kilometrage']) . ' km' : 'Ex: 45000' ?>"
                 min="0" value="">
          <?php if ($res['kilometrage']): ?>
          <div class="form-text">Km actuel du véhicule : <?= number_format($res['kilometrage']) ?> km</div>
          <?php endif; ?>
        </div>

        <!-- Propreté -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Propreté</label>
          <div class="d-flex gap-2 mt-1">
            <?php foreach (['propre' => ['Propre','btn-success'], 'moyen' => ['Moyen','btn-warning text-dark'], 'sale' => ['Sale','btn-danger']] as $k => [$label, $btnClass]): ?>
            <input type="radio" name="proprete" id="p_<?= $k ?>" value="<?= $k ?>" class="d-none" <?= $k === 'propre' ? 'checked' : '' ?>>
            <label for="p_<?= $k ?>" class="btn btn-sm btn-outline-secondary proprete-btn" data-val="<?= $k ?>"><?= $label ?></label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Rayures -->
        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="rayures" id="rayures">
            <label class="form-check-label" for="rayures">
              <strong>Rayures / égratignures observées</strong>
            </label>
          </div>
        </div>

        <!-- Dommages -->
        <div class="col-12">
          <label class="form-label fw-semibold">Description des dommages</label>
          <textarea name="dommages" class="form-control" rows="3" placeholder="Décrivez les dommages observés (rayures, bosses, fissures…)"></textarea>
        </div>

        <!-- Notes -->
        <div class="col-12">
          <label class="form-label fw-semibold">Notes générales</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Observations, remarques…"></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between">
          <a href="/location/reservations/view.php?id=<?= $reservationId ?>" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-success px-4">Enregistrer l'état</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setFuel(val) {
  document.querySelectorAll('.fuel-seg').forEach(el => {
    el.classList.toggle('active', parseInt(el.dataset.v) <= val);
  });
  document.getElementById('carburantInput').value = val;
}

// Propreté toggle
document.querySelectorAll('.proprete-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.proprete-btn').forEach(b => b.classList.remove('btn-success','btn-warning','btn-danger','active'));
    const map = {propre:'btn-success',moyen:'btn-warning text-dark',sale:'btn-danger'};
    this.classList.add(map[this.dataset.val] || 'btn-secondary');
  });
});

// Init propreté button
document.querySelector('[data-val="propre"]').classList.add('btn-success');
</script>
</body>
</html>
