<?php
require_once "../config/database.php";
require_once "../models/MissionModel.php";
require_once "../models/TrajetModel.php";
require_once "../models/MissionTeamModel.php";

$db = new Database();
$conn = $db->getConnection();

$missionModel = new MissionModel($conn);
$trajetModel = new TrajetModel($conn);
$teamModel = new MissionTeamModel($conn);

$id = $_GET['id'];

// DATA
$mission = $missionModel->getById($id);

$trajets = $conn->prepare("SELECT * FROM trajets WHERE mission_id=? ORDER BY ordre");
$trajets->execute([$id]);
$trajets = $trajets->fetchAll();

$team = $conn->prepare("SELECT * FROM mission_team WHERE mission_id=?");
$team->execute([$id]);
$team = $team->fetchAll();

$vehicles = $conn->query("SELECT * FROM vehicles")->fetchAll();
$chauffeurs = $conn->query("SELECT * FROM chauffeurs")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        $conn->beginTransaction();

        // UPDATE mission
        $stmt = $conn->prepare("UPDATE missions SET reference=?, description=? WHERE id=?");
        $stmt->execute([
            $_POST['reference'],
            $_POST['description'],
            $id
        ]);

        // RESET old data
        $trajetModel->deleteByMission($id);
        $teamModel->deleteByMission($id);

        // TRAJETS
        foreach ($_POST['ville_depart'] as $i => $vd) {

            if (!empty($vd)) {
                $trajetModel->create([
                    ":mission_id" => $id,
                    ":ville_depart" => $vd,
                    ":ville_arrivee" => $_POST['ville_arrivee'][$i],
                    ":date_depart" => $_POST['date_depart'][$i],
                    ":date_arrivee" => $_POST['date_arrivee'][$i],
                    ":ordre" => $i + 1
                ]);
            }
        }

        // TEAM
        foreach ($_POST['vehicle_id'] as $i => $v) {

            $driver = $_POST['chauffeur_id'][$i] ?? null;
            $chef = $_POST['chef_id'][$i] ?? null;

            if ($driver) {
                $teamModel->add($id, $v, $driver, "chauffeur");
            }

            if ($chef) {
                $teamModel->add($id, $v, $chef, "chef");
            }
        }

        $conn->commit();
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Erreur: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier mission</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

    <h2 class="mb-3">✏️ Modifier mission</h2>

    <form method="POST">

        <!-- MISSION -->
        <div class="card mb-3 shadow-sm">
            <div class="card-body">

                <h5>📦 Informations mission</h5>

                <input name="reference" class="form-control mb-2"
                       value="<?= htmlspecialchars($mission['reference']) ?>">

                <textarea name="description" class="form-control"
                          placeholder="Description"><?= htmlspecialchars($mission['description']) ?></textarea>

            </div>
        </div>

        <!-- TRAJETS -->
        <div class="card mb-3 shadow-sm">
            <div class="card-body">

                <h5>🛣️ Trajets</h5>

                <div id="trajets">

                <?php foreach($trajets as $t): ?>
                    <div class="row g-2 mb-2">

                        <div class="col">
                            <input class="form-control" name="ville_depart[]"
                                   value="<?= htmlspecialchars($t['ville_depart']) ?>"
                                   placeholder="Départ">
                        </div>

                        <div class="col">
                            <input class="form-control" name="ville_arrivee[]"
                                   value="<?= htmlspecialchars($t['ville_arrivee']) ?>"
                                   placeholder="Arrivée">
                        </div>

                        <div class="col">
                            <input class="form-control" type="datetime-local"
                                   name="date_depart[]"
                                   value="<?= date('Y-m-d\TH:i', strtotime($t['date_depart'])) ?>">
                        </div>

                        <div class="col">
                            <input class="form-control" type="datetime-local"
                                   name="date_arrivee[]"
                                   value="<?= date('Y-m-d\TH:i', strtotime($t['date_arrivee'])) ?>">
                        </div>

                        <div class="col-auto">
                            <button type="button" class="btn btn-danger"
                                    onclick="this.parentElement.parentElement.remove()">❌</button>
                        </div>

                    </div>
                <?php endforeach; ?>

                </div>

                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addTrajet()">
                    + Ajouter trajet
                </button>

            </div>
        </div>

        <!-- TEAM -->
        <div class="card mb-3 shadow-sm">
            <div class="card-body">

                <h5>🚛 Équipe</h5>

                <div id="team">

                <?php foreach($team as $tm): ?>
                    <div class="row g-2 mb-2">

                        <div class="col">
                            <select name="vehicle_id[]" class="form-select">
                                <?php foreach($vehicles as $v): ?>
                                    <option value="<?= $v['id'] ?>"
                                        <?= $v['id']==$tm['vehicle_id']?'selected':'' ?>>
                                        <?= $v['numero'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col">
                            <select name="chauffeur_id[]" class="form-select">
                                <option value="">Chauffeur</option>
                                <?php foreach($chauffeurs as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= $c['nom'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col">
                            <select name="chef_id[]" class="form-select">
                                <option value="">Chef</option>
                                <?php foreach($chauffeurs as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= $c['nom'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-auto">
                            <button type="button" class="btn btn-danger"
                                    onclick="this.parentElement.parentElement.remove()">❌</button>
                        </div>

                    </div>
                <?php endforeach; ?>

                </div>

                <button type="button" class="btn btn-outline-success btn-sm mt-2" onclick="addTeam()">
                    + Ajouter équipe
                </button>

            </div>
        </div>

        <!-- ACTIONS -->
        <div class="d-flex justify-content-between">

            <a href="index.php" class="btn btn-secondary">
                ← Retour
            </a>

            <button class="btn btn-success">
                💾 Enregistrer
            </button>

        </div>

    </form>

</div>

<script>
function addTrajet(){
    let div = document.createElement("div");
    div.className = "row g-2 mb-2";

    div.innerHTML = `
        <div class="col"><input class="form-control" name="ville_depart[]" placeholder="Départ"></div>
        <div class="col"><input class="form-control" name="ville_arrivee[]" placeholder="Arrivée"></div>
        <div class="col"><input class="form-control" type="datetime-local" name="date_depart[]"></div>
        <div class="col"><input class="form-control" type="datetime-local" name="date_arrivee[]"></div>
        <div class="col-auto"><button type="button" class="btn btn-danger">❌</button></div>
    `;

    div.querySelector("button").onclick = () => div.remove();
    document.getElementById("trajets").appendChild(div);
}

function addTeam(){
    let div = document.createElement("div");
    div.className = "row g-2 mb-2";

    div.innerHTML = `
        <div class="col"><select name="vehicle_id[]" class="form-select"><?php foreach($vehicles as $v): ?><option value="<?= $v['id'] ?>"><?= $v['numero'] ?></option><?php endforeach; ?></select></div>
        <div class="col"><select name="chauffeur_id[]" class="form-select"><?php foreach($chauffeurs as $c): ?><option value="<?= $c['id'] ?>"><?= $c['nom'] ?></option><?php endforeach; ?></select></div>
        <div class="col"><select name="chef_id[]" class="form-select"><?php foreach($chauffeurs as $c): ?><option value="<?= $c['id'] ?>"><?= $c['nom'] ?></option><?php endforeach; ?></select></div>
        <div class="col-auto"><button type="button" class="btn btn-danger">❌</button></div>
    `;

    div.querySelector("button").onclick = () => div.remove();
    document.getElementById("team").appendChild(div);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>