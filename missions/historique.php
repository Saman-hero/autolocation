<?php
require_once "../config/database.php";

$db = new Database();
$conn = $db->getConnection();

// FILTRES
$date_debut = $_GET['date_debut'] ?? null;
$date_fin   = $_GET['date_fin'] ?? null;
$chauffeur  = $_GET['chauffeur'] ?? null;
$vehicle    = $_GET['vehicle'] ?? null;

// LISTES POUR SELECT
$chauffeurs = $conn->query("SELECT * FROM chauffeurs")->fetchAll();
$vehicles   = $conn->query("SELECT * FROM vehicles")->fetchAll();

// REQUETE PRINCIPALE
$sql = "
SELECT 
    m.id,
    m.reference,
    m.statut,
    m.created_at,
    v.numero AS vehicle,
    c.nom AS chauffeur,
    mt.role
FROM missions m
LEFT JOIN mission_team mt ON m.id = mt.mission_id
LEFT JOIN vehicles v ON mt.vehicle_id = v.id
LEFT JOIN chauffeurs c ON mt.chauffeur_id = c.id
WHERE 1=1
";

// FILTRE DATE
if ($date_debut && $date_fin) {
    $sql .= " AND DATE(m.created_at) BETWEEN '$date_debut' AND '$date_fin'";
}

// FILTRE CHAUFFEUR
if ($chauffeur) {
    $sql .= " AND c.id = $chauffeur";
}

// FILTRE VEHICULE
if ($vehicle) {
    $sql .= " AND v.id = $vehicle";
}

$sql .= " ORDER BY m.created_at DESC";

$data = $conn->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

    <h2 class="mb-3">📊 Historique des missions</h2>

    <!-- FILTRES -->
    <form method="GET" class="row g-2 mb-3">

        <div class="col">
            <input type="date" name="date_debut" class="form-control" value="<?= $date_debut ?>">
        </div>

        <div class="col">
            <input type="date" name="date_fin" class="form-control" value="<?= $date_fin ?>">
        </div>

        <div class="col">
            <select name="chauffeur" class="form-select">
                <option value="">Tous chauffeurs</option>
                <?php foreach($chauffeurs as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($chauffeur==$c['id'])?'selected':'' ?>>
                        <?= $c['nom'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col">
            <select name="vehicle" class="form-select">
                <option value="">Tous véhicules</option>
                <?php foreach($vehicles as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= ($vehicle==$v['id'])?'selected':'' ?>>
                        <?= $v['numero'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col">
            <button class="btn btn-primary w-100">Filtrer</button>
        </div>

    </form>

    <!-- TABLE -->
    <div class="card">
        <div class="card-body">

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Référence</th>
                        <th>Chauffeur</th>
                        <th>Véhicule</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach($data as $d): ?>
                    <tr>
                        <td><?= $d['id'] ?></td>
                        <td><?= $d['reference'] ?></td>
                        <td><?= $d['chauffeur'] ?></td>
                        <td><?= $d['vehicle'] ?></td>
                        <td><?= $d['role'] ?></td>
                        <td><?= $d['statut'] ?></td>
                        <td><?= $d['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

            </table>

        </div>
    </div>

</div>

</body>
</html>