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

// data
$vehicles = $conn->query("SELECT * FROM vehicles")->fetchAll();
$chauffeurs = $conn->query("SELECT * FROM chauffeurs")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {

        $conn->beginTransaction();

        // 1️⃣ mission
        $mission_id = $missionModel->create([
            ":reference" => $_POST['reference'],
            ":description" => $_POST['description'],
            ":statut" => "en cours"
        ]);

        // 2️⃣ trajets
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

        // 3️⃣ TEAM
        $vehicles_ids = $_POST['vehicle_id'];
        $drivers = $_POST['chauffeur_id'];
        $chefs = $_POST['chef_id'];

        foreach ($vehicles_ids as $v) {

            // chauffeurs
            foreach ($drivers as $c) {
                $teamModel->add($mission_id, $v, $c, "chauffeur");
            }

            // chefs
            foreach ($chefs as $c) {
                $teamModel->add($mission_id, $v, $c, "chef");
            }
        }

        $conn->commit();

        header("Location: index.php");

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Erreur: " . $e->getMessage();
    }
}
?>

<h2>🚚 Créer mission</h2>

<form method="POST">

<input name="reference" placeholder="Référence"><br>
<textarea name="description"></textarea>

<hr>

<h3>Trajets</h3>
<div id="trajets">
<div>
<input name="ville_depart[]" placeholder="Départ">
<input name="ville_arrivee[]" placeholder="Arrivée">
<input type="datetime-local" name="date_depart[]">
<input type="datetime-local" name="date_arrivee[]">
</div>
</div>

<button type="button" onclick="addTrajet()">+ étape</button>

<hr>

<h3>Véhicules</h3>
<select name="vehicle_id[]" multiple>
<?php foreach($vehicles as $v): ?>
<option value="<?= $v['id'] ?>"><?= $v['numero'] ?></option>
<?php endforeach; ?>
</select>

<h3>Chauffeurs</h3>
<select name="chauffeur_id[]" multiple>
<?php foreach($chauffeurs as $c): ?>
<option value="<?= $c['id'] ?>"><?= $c['nom'] ?></option>
<?php endforeach; ?>
</select>

<h3>Chefs</h3>
<select name="chef_id[]" multiple>
<?php foreach($chauffeurs as $c): ?>
<option value="<?= $c['id'] ?>"><?= $c['nom'] ?></option>
<?php endforeach; ?>
</select>

<br><br>
<button>Créer</button>

</form>

<script>
function addTrajet() {
    let div = document.createElement("div");
    div.innerHTML = `
    <input name="ville_depart[]" placeholder="Départ">
    <input name="ville_arrivee[]" placeholder="Arrivée">
    <input type="datetime-local" name="date_depart[]">
    <input type="datetime-local" name="date_arrivee[]">
    `;
    document.getElementById("trajets").appendChild(div);
}
</script>