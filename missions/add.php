<?php
require_once "../config/database.php";
require_once "../models/MissionModel.php";
require_once "../models/TrajetModel.php";
require_once "../models/MissionTeamModel.php";

$db = new Database();
$conn = $db->getConnection();

$missionModel = new MissionModel($conn);
$trajetModel  = new TrajetModel($conn);
$teamModel    = new MissionTeamModel($conn);

//
// 🔥 DISPONIBILITÉ
//
$vehicles = $conn->query("
    SELECT * FROM vehicles WHERE statut = 'disponible'
")->fetchAll();

$chauffeurs = $conn->query("
    SELECT * FROM chauffeurs WHERE statut = 'disponible'
")->fetchAll();

$chefs = $chauffeurs;

//
// 🔥 CREATE MISSION
//
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        $conn->beginTransaction();

        // 1. CREATE MISSION
        $mission_id = $missionModel->create([
            ":reference" => $_POST['reference'],
            ":description" => $_POST['description'],
            ":statut" => "en cours"
        ]);

        // 2. TRAJETS
        if (!empty($_POST['ville_depart'])) {
            foreach ($_POST['ville_depart'] as $i => $vd) {

                if (!empty($vd)) {
                    $trajetModel->create([
                        ":mission_id" => $mission_id,
                        ":ville_depart" => $vd,
                        ":ville_arrivee" => $_POST['ville_arrivee'][$i],
                        ":date_depart" => $_POST['date_depart'][$i],
                        ":date_arrivee" => $_POST['date_arrivee'][$i],
                        ":ordre" => $i + 1
                    ]);
                }
            }
        }

        // 3. TEAM + STATUT + HISTORIQUE
        if (!empty($_POST['vehicle_id'])) {

            foreach ($_POST['vehicle_id'] as $i => $v) {

                $driver = $_POST['chauffeur_id'][$i] ?? null;
                $chef   = $_POST['chef_id'][$i] ?? null;

                // 🚗 VEHICLE → EN MISSION
                $conn->prepare("
                    UPDATE vehicles SET statut='en mission' WHERE id=?
                ")->execute([$v]);

                // 👨‍✈️ CHAUFFEUR
                if (!empty($driver)) {

                    $teamModel->add($mission_id, $v, $driver, "chauffeur");

                    $conn->prepare("
                        INSERT INTO mission_affectations
                        (mission_id, vehicle_id, chauffeur_id, role)
                        VALUES (?, ?, ?, ?)
                    ")->execute([
                        $mission_id,
                        $v,
                        $driver,
                        "chauffeur"
                    ]);

                    $conn->prepare("
                        UPDATE chauffeurs SET statut='en mission' WHERE id=?
                    ")->execute([$driver]);
                }

                // 👨‍✈️ CHEF
                if (!empty($chef)) {

                    $teamModel->add($mission_id, $v, $chef, "chef");

                    $conn->prepare("
                        INSERT INTO mission_affectations
                        (mission_id, vehicle_id, chauffeur_id, role)
                        VALUES (?, ?, ?, ?)
                    ")->execute([
                        $mission_id,
                        $v,
                        $chef,
                        "chef"
                    ]);

                    $conn->prepare("
                        UPDATE chauffeurs SET statut='en mission' WHERE id=?
                    ")->execute([$chef]);
                }
            }
        }

        $conn->commit();
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "<div class='alert alert-danger m-3'>Erreur : " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Créer mission</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

<h2 class="mb-3">🚚 Créer mission</h2>

<form method="POST">

<!-- MISSION -->
<div class="card mb-3">
    <div class="card-body">

        <input name="reference" class="form-control mb-2"
               placeholder="Référence mission" required>

        <textarea name="description" class="form-control"
                  placeholder="Description"></textarea>

    </div>
</div>

<!-- TRAJETS -->
<div class="card mb-3">
    <div class="card-body">

        <h5>🛣️ Trajets</h5>
        <div id="trajets"></div>

        <button type="button" class="btn btn-primary btn-sm mt-2"
                onclick="addTrajet()">
            + Ajouter trajet
        </button>

    </div>
</div>

<!-- TEAM -->
<div class="card mb-3">
    <div class="card-body">

        <h5>👥 Équipe</h5>
        <div id="team"></div>

        <button type="button" class="btn btn-success btn-sm mt-2"
                onclick="addTeam()">
            + Ajouter équipe
        </button>

    </div>
</div>

<button class="btn btn-primary">
🚀 Créer mission
</button>

</form>

</div>

<script>

function addTrajet() {
    let div = document.createElement("div");
    div.className = "row g-2 mb-2";

    div.innerHTML = `
        <div class="col"><input class="form-control" name="ville_depart[]" placeholder="Départ"></div>
        <div class="col"><input class="form-control" name="ville_arrivee[]" placeholder="Arrivée"></div>
        <div class="col"><input class="form-control" type="datetime-local" name="date_depart[]"></div>
        <div class="col"><input class="form-control" type="datetime-local" name="date_arrivee[]"></div>
        <div class="col-auto">
            <button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.remove()">❌</button>
        </div>
    `;

    document.getElementById("trajets").appendChild(div);
}

function addTeam() {
    let div = document.createElement("div");
    div.className = "row g-2 mb-2";

    div.innerHTML = `
        <div class="col">
            <select name="vehicle_id[]" class="form-select" required>
                <?php foreach($vehicles as $v): ?>
                    <option value="<?= $v['id'] ?>">
                        <?= $v['numero'] ?> - <?= $v['marque'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col">
            <select name="chauffeur_id[]" class="form-select">
                <option value="">Chauffeur</option>
                <?php foreach($chauffeurs as $c): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= $c['nom'] ?> <?= $c['prenom'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col">
            <select name="chef_id[]" class="form-select">
                <option value="">Chef</option>
                <?php foreach($chefs as $c): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= $c['nom'] ?> <?= $c['prenom'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-auto">
            <button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.remove()">❌</button>
        </div>
    `;

    document.getElementById("team").appendChild(div);
}

</script>

</body>
</html>