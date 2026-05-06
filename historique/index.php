<?php
require_once "../config/database.php";

$db = new Database();
$conn = $db->getConnection();

// 🔍 filtres
$chauffeur_id = $_GET['chauffeur_id'] ?? null;
$vehicle_id   = $_GET['vehicle_id'] ?? null;
$from_date    = $_GET['from'] ?? null;
$to_date      = $_GET['to'] ?? null;

// 🔥 LISTES FILTRES
$chauffeurs = $conn->query("SELECT id, nom, prenom FROM chauffeurs")->fetchAll();
$vehicles   = $conn->query("SELECT id, numero FROM vehicles")->fetchAll();

// 🔥 QUERY BASE
$sql = "
SELECT 
    m.id,
    m.reference,
    m.description,
    m.statut,
    m.created_at
FROM missions m
LEFT JOIN mission_team mt ON m.id = mt.mission_id
WHERE 1=1
";

$params = [];

// 🔎 filtre chauffeur
if (!empty($chauffeur_id)) {
    $sql .= " AND mt.chauffeur_id = :chauffeur_id";
    $params[':chauffeur_id'] = $chauffeur_id;
}

// 🔎 filtre véhicule
if (!empty($vehicle_id)) {
    $sql .= " AND mt.vehicle_id = :vehicle_id";
    $params[':vehicle_id'] = $vehicle_id;
}

// 🔎 filtre date
if (!empty($from_date)) {
    $sql .= " AND DATE(m.created_at) >= :from_date";
    $params[':from_date'] = $from_date;
}

if (!empty($to_date)) {
    $sql .= " AND DATE(m.created_at) <= :to_date";
    $params[':to_date'] = $to_date;
}

$sql .= " GROUP BY m.id ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$missions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des missions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

    <h2 class="mb-3">📊 Historique des missions</h2>

    <!-- 🔎 FILTRES -->
    <div class="card mb-3">
        <div class="card-body">

            <form method="GET" class="row g-2">

                <!-- chauffeur -->
                <div class="col-md-3">
                    <select name="chauffeur_id" class="form-select">
                        <option value="">👨‍✈️ Chauffeur</option>
                        <?php foreach($chauffeurs as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($chauffeur_id == $c['id']) ? 'selected' : '' ?>>
                                <?= $c['nom'] ?> <?= $c['prenom'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- véhicule -->
                <div class="col-md-3">
                    <select name="vehicle_id" class="form-select">
                        <option value="">🚗 Véhicule</option>
                        <?php foreach($vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>"
                                <?= ($vehicle_id == $v['id']) ? 'selected' : '' ?>>
                                <?= $v['numero'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- date from -->
                <div class="col-md-2">
                    <input type="date" name="from" class="form-control"
                           value="<?= $from_date ?>">
                </div>

                <!-- date to -->
                <div class="col-md-2">
                    <input type="date" name="to" class="form-control"
                           value="<?= $to_date ?>">
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary">🔎 Filtrer</button>
                </div>

            </form>

        </div>
    </div>

    <!-- 📦 TABLE -->
    <div class="card">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover">

                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Référence</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach($missions as $m): ?>
                        <tr>
                            <td><?= $m['id'] ?></td>
                            <td><strong><?= $m['reference'] ?></strong></td>
                            <td><?= $m['description'] ?></td>

                            <td>
                                <?php if($m['statut']=='terminée'): ?>
                                    <span class="badge bg-success">Terminée</span>
                                <?php elseif($m['statut']=='en cours'): ?>
                                    <span class="badge bg-warning text-dark">En cours</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <?= $m['statut'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

</body>
</html>