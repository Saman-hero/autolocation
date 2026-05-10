<?php
require_once "../config/database.php";
require_once "XlsxReader.php";

$db   = new Database();
$conn = $db->getConnection();

$errors  = [];
$preview = [];
$step    = 'upload';

/* ─── TEMPLATE DOWNLOAD ─────────────────────────────────────────────── */
if (isset($_GET['template'])) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="modele_vehicules.xlsx.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['numero','marque','modele','type','annee','kilometrage','statut','type_vidange','intervalle_vidange','derniere_vidange_km','date_derniere_vidange'], ';');
    fputcsv($out, ['VHL-001','Toyota','Land Cruiser','VIP','2019','45000','disponible','Synthétique 5W-30','10000','40000','2024-11-01'], ';');
    fclose($out);
    exit;
}

/* ─── STEP 2: CONFIRM & INSERT ──────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $rows   = json_decode($_POST['rows_json'], true);
    $ok     = 0;
    $failed = [];

    foreach ($rows as $r) {
        try {
            $conn->prepare("
                INSERT INTO vehicles
                  (numero, marque, modele, type, annee, kilometrage, statut,
                   type_vidange, intervalle_vidange, derniere_vidange_km, date_derniere_vidange)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $r['numero'], $r['marque'], $r['modele'], $r['type'],
                $r['annee'] ?: null, $r['kilometrage'] ?: 0,
                $r['statut'] ?: 'disponible',
                $r['type_vidange'] ?: null,
                $r['intervalle_vidange'] ?: 10000,
                $r['derniere_vidange_km'] ?: 0,
                $r['date_derniere_vidange'] ?: null,
            ]);
            $ok++;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $failed[] = strpos($msg, 'Duplicate') !== false
                ? "Ligne {$r['_row']} ({$r['numero']}) : Numéro déjà existant"
                : "Ligne {$r['_row']} : $msg";
        }
    }

    flash('success', "$ok véhicule(s) importé(s)." . (count($failed) ? ' ' . count($failed) . ' erreur(s).' : ''));
    header("Location: ../vehicles/index.php"); exit;
}

/* ─── STEP 1: PARSE XLSX ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xlsxfile'])) {
    $step = 'preview';

    if ($_FILES['xlsxfile']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Erreur lors de l'upload."; $step = 'upload';
    } else {
        try {
            $reader = new XlsxReader();
            $rows   = $reader->read($_FILES['xlsxfile']['tmp_name']);

            if (empty($rows)) {
                $errors[] = "Fichier vide ou sans données."; $step = 'upload';
            } else {
                $required   = ['numero','marque','type'];
                $header     = array_keys($rows[0]);
                $missing    = array_diff($required, $header);
                if ($missing) {
                    $errors[] = "Colonnes manquantes : " . implode(', ', $missing); $step = 'upload';
                } else {
                    $validTypes = ['VIP','camion','transport personnel'];
                    foreach ($rows as $i => $data) {
                        $rowNum    = $i + 2;
                        $rowErrors = [];

                        if (empty($data['numero'])) $rowErrors[] = 'Numéro requis';
                        if (empty($data['marque']))  $rowErrors[] = 'Marque requise';
                        if (empty($data['type']))    $rowErrors[] = 'Type requis';

                        if (!empty($data['type']) && !in_array($data['type'], $validTypes)) {
                            $rowErrors[] = "Type invalide (VIP / camion / transport personnel)";
                        }
                        if (!empty($data['numero'])) {
                            $chk = $conn->prepare("SELECT id FROM vehicles WHERE numero=?");
                            $chk->execute([$data['numero']]);
                            if ($chk->fetch()) $rowErrors[] = 'Numéro déjà existant';
                        }

                        $preview[] = [
                            '_row'                 => $rowNum,
                            '_errors'              => $rowErrors,
                            'numero'               => trim($data['numero'] ?? ''),
                            'marque'               => trim($data['marque'] ?? ''),
                            'modele'               => trim($data['modele'] ?? ''),
                            'type'                 => trim($data['type'] ?? ''),
                            'annee'                => trim($data['annee'] ?? ''),
                            'kilometrage'          => trim($data['kilometrage'] ?? '0'),
                            'statut'               => trim($data['statut'] ?? 'disponible'),
                            'type_vidange'         => trim($data['type_vidange'] ?? ''),
                            'intervalle_vidange'   => trim($data['intervalle_vidange'] ?? '10000'),
                            'derniere_vidange_km'  => trim($data['derniere_vidange_km'] ?? '0'),
                            'date_derniere_vidange'=> trim($data['date_derniere_vidange'] ?? ''),
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage(); $step = 'upload';
        }
    }
}

$validRows   = array_filter($preview, fn($r) => empty($r['_errors']));
$invalidRows = array_filter($preview, fn($r) => !empty($r['_errors']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Import Véhicules (Excel)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container-fluid px-4 py-4" style="max-width:1200px">

  <div class="page-header">
    <div>
      <div class="text-muted small mb-1">
        <a href="../vehicles/index.php" class="text-decoration-none text-muted">Véhicules</a> / Import Excel
      </div>
      <h1 class="page-title">Import en masse — Véhicules (.xlsx)</h1>
    </div>
    <div class="d-flex gap-2">
      <a href="?template=1" class="btn btn-outline-success">⬇ Télécharger modèle</a>
      <a href="../vehicles/index.php" class="btn btn-outline-secondary">← Retour</a>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($step === 'upload'): ?>
  <div class="card mb-4">
    <div class="card-header">Instructions</div>
    <div class="card-body">
      <ol class="mb-3">
        <li>Téléchargez le <a href="?template=1">modèle</a> et ouvrez-le dans Excel.</li>
        <li>Remplissez les données (une ligne par véhicule). Ne modifiez pas les en-têtes.</li>
        <li>Enregistrez au format <strong>.xlsx</strong>.</li>
        <li>Uploadez le fichier ci-dessous.</li>
      </ol>
      <table class="table table-sm table-bordered small">
        <thead class="table-light">
          <tr><th>Colonne</th><th>Obligatoire</th><th>Format / Valeurs</th><th>Exemple</th></tr>
        </thead>
        <tbody>
          <tr><td>numero</td><td><span class="badge bg-danger">Oui</span></td><td>Texte unique</td><td>VHL-001</td></tr>
          <tr><td>marque</td><td><span class="badge bg-danger">Oui</span></td><td>Texte</td><td>Toyota</td></tr>
          <tr><td>type</td><td><span class="badge bg-danger">Oui</span></td><td>VIP / camion / transport personnel</td><td>VIP</td></tr>
          <tr><td>modele</td><td><span class="badge bg-secondary">Non</span></td><td>Texte</td><td>Land Cruiser</td></tr>
          <tr><td>annee</td><td><span class="badge bg-secondary">Non</span></td><td>Entier</td><td>2019</td></tr>
          <tr><td>kilometrage</td><td><span class="badge bg-secondary">Non</span></td><td>Entier</td><td>45000</td></tr>
          <tr><td>statut</td><td><span class="badge bg-secondary">Non</span></td><td>disponible / maintenance</td><td>disponible</td></tr>
          <tr><td>type_vidange</td><td><span class="badge bg-secondary">Non</span></td><td>Texte</td><td>Synthétique 5W-30</td></tr>
          <tr><td>intervalle_vidange</td><td><span class="badge bg-secondary">Non</span></td><td>Entier (km)</td><td>10000</td></tr>
          <tr><td>derniere_vidange_km</td><td><span class="badge bg-secondary">Non</span></td><td>Entier (km)</td><td>40000</td></tr>
          <tr><td>date_derniere_vidange</td><td><span class="badge bg-secondary">Non</span></td><td>YYYY-MM-DD</td><td>2024-11-01</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Choisir un fichier Excel (.xlsx)</div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <input type="file" name="xlsxfile" class="form-control" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
          <div class="form-text">Fichier Excel .xlsx uniquement.</div>
        </div>
        <button class="btn btn-primary">Analyser le fichier →</button>
      </form>
    </div>
  </div>

  <?php else: ?>
  <div class="d-flex gap-3 mb-3">
    <div class="card text-center px-4 py-2 flex-fill">
      <div class="fs-4 fw-bold text-success"><?= count($validRows) ?></div>
      <div class="small text-muted">Prêts à importer</div>
    </div>
    <div class="card text-center px-4 py-2 flex-fill">
      <div class="fs-4 fw-bold text-danger"><?= count($invalidRows) ?></div>
      <div class="small text-muted">Lignes avec erreurs</div>
    </div>
    <div class="card text-center px-4 py-2 flex-fill">
      <div class="fs-4 fw-bold"><?= count($preview) ?></div>
      <div class="small text-muted">Total lignes</div>
    </div>
  </div>

  <?php if (!empty($invalidRows)): ?>
  <div class="alert alert-warning">⚠ <?= count($invalidRows) ?> ligne(s) ignorée(s).</div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
      <span>Aperçu (<?= count($preview) ?> lignes)</span>
      <a href="vehicles.php" class="btn btn-sm btn-outline-secondary">↩ Changer de fichier</a>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 small">
        <thead class="table-light">
          <tr><th>#</th><th>Numéro</th><th>Marque</th><th>Modèle</th><th>Type</th><th>Année</th><th>Km</th><th>Statut</th><th>Résultat</th></tr>
        </thead>
        <tbody>
          <?php foreach ($preview as $r): ?>
          <tr class="<?= !empty($r['_errors']) ? 'table-danger' : '' ?>">
            <td><?= $r['_row'] ?></td>
            <td><?= htmlspecialchars($r['numero']) ?></td>
            <td><?= htmlspecialchars($r['marque']) ?></td>
            <td><?= htmlspecialchars($r['modele']) ?></td>
            <td><?= htmlspecialchars($r['type']) ?></td>
            <td><?= htmlspecialchars($r['annee']) ?></td>
            <td><?= number_format((int)$r['kilometrage']) ?></td>
            <td><?= htmlspecialchars($r['statut']) ?></td>
            <td>
              <?php if (!empty($r['_errors'])): ?>
                <span class="text-danger" title="<?= htmlspecialchars(implode(' | ', $r['_errors'])) ?>">✕ <?= htmlspecialchars($r['_errors'][0]) ?></span>
              <?php else: ?>
                <span class="text-success">✔ OK</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (count($validRows) > 0): ?>
  <form method="POST">
    <input type="hidden" name="confirm" value="1">
    <input type="hidden" name="rows_json" value="<?= htmlspecialchars(json_encode(array_values($validRows))) ?>">
    <div class="d-flex gap-2 justify-content-end">
      <a href="vehicles.php" class="btn btn-outline-secondary">Annuler</a>
      <button class="btn btn-success px-4">✔ Importer <?= count($validRows) ?> véhicule(s)</button>
    </div>
  </form>
  <?php else: ?>
  <div class="text-center py-3">
    <p class="text-danger">Aucune ligne valide. Corrigez le fichier et réessayez.</p>
    <a href="vehicles.php" class="btn btn-outline-secondary">↩ Réessayer</a>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
