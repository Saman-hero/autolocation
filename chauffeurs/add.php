<?php
require_once "../config/database.php";
require_once "../models/ChauffeurModel.php";
require_once "../models/VehicleModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new ChauffeurModel($conn);

$vehicleModel = new VehicleModel($conn);
$vehicles = $vehicleModel->getFreeVehicles();

if($_SERVER["REQUEST_METHOD"] == "POST") {

    $model->create([
        ":nom" => $_POST['nom'],
        ":prenom" => $_POST['prenom'],
        ":telephone" => $_POST['telephone'],
        ":adresse" => $_POST['adresse'],
        ":date_embauche" => $_POST['date_embauche'],
        ":grade" => $_POST['grade'],
        ":matricule" => $_POST['matricule'],
        ":cine" => $_POST['cine'],
        ":statut" => $_POST['statut'],
        ":niveau" => $_POST['niveau'],
        ":type_permis" => $_POST['type_permis'],
        ":vehicle_id" => $_POST['vehicle_id'] ?: null,
        ":lieu_detachement" => $_POST['statut'] == 'détaché' ? $_POST['lieu_detachement'] : null,
        ":date_detachement" => $_POST['statut'] == 'détaché' ? $_POST['date_detachement'] : null
    ]);

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ajouter chauffeur</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

<h2>➕ Ajouter chauffeur</h2>

<form method="POST" class="row g-3">

<!-- Nom -->
<div class="col-md-6">
<label class="form-label">Nom</label>
<input name="nom" class="form-control" required>
</div>

<!-- Prénom -->
<div class="col-md-6">
<label class="form-label">Prénom</label>
<input name="prenom" class="form-control" required>
</div>

<!-- Téléphone -->
<div class="col-md-6">
<label class="form-label">Téléphone</label>
<input name="telephone" class="form-control">
</div>

<!-- Date embauche -->
<div class="col-md-6">
<label class="form-label">Date d'embauche</label>
<input type="date" name="date_embauche" class="form-control">
</div>

<!-- Grade -->
<div class="col-md-6">
<label class="form-label">Grade</label>
<select name="grade" class="form-select">
<option>adjudant-chef</option>
<option>adjudant</option>
<option>sergent-chef</option>
<option>sergent</option>
<option>caporal chef</option>
<option>caporal</option>
<option>1 classe</option>
<option>2 classe</option>
</select>
</div>

<!-- Niveau -->
<div class="col-md-6">
<label class="form-label">Niveau</label>
<select name="niveau" class="form-select">
<option>débutant</option>
<option>moyen</option>
<option>professionnel</option>
</select>
</div>

<!-- Permis -->
<div class="col-md-6">
<label class="form-label">Type permis</label>
<input name="type_permis" class="form-control">
</div>

<!-- Véhicule -->
<div class="col-md-6">
<label class="form-label">Véhicule responsable (optionnel)</label>
<select name="vehicle_id" class="form-select">
    <option value="">-- Aucun véhicule --</option>
    <?php foreach($vehicles as $v): ?>
        <option value="<?= $v['id'] ?>">
            <?= $v['numero'] ?> - <?= $v['marque'] ?> <?= $v['modele'] ?>
        </option>
    <?php endforeach; ?>
</select>
</div>

<!-- Matricule -->
<div class="col-md-6">
<label class="form-label">Matricule</label>
<input name="matricule" class="form-control" required>
</div>

<!-- CIN -->
<div class="col-md-6">
<label class="form-label">CIN</label>
<input name="cine" class="form-control" required>
</div>

<!-- Statut -->
<div class="col-md-6">
<label class="form-label">Statut</label>
<select name="statut" id="statut" class="form-select" onchange="toggleDet()">
<option value="disponible">disponible</option>
<option value="en mission">en mission</option>
<option value="détaché">détaché</option>
<option value="congé">congé</option>
</select>
</div>

<!-- Détachement -->
<div id="detachement" class="col-12" style="display:none">

<label class="form-label">Lieu détachement</label>
<input name="lieu_detachement" class="form-control mb-2">

<label class="form-label">Date détachement</label>
<input type="date" name="date_detachement" class="form-control">

</div>

<!-- Adresse -->
<div class="col-12">
<label class="form-label">Adresse</label>
<textarea name="adresse" class="form-control"></textarea>
</div>

<!-- Submit -->
<div class="col-12">
<button class="btn btn-success">Ajouter chauffeur</button>
</div>

</form>

</div>

<script>
function toggleDet(){
    let s = document.getElementById('statut').value;
    document.getElementById('detachement').style.display =
        (s === 'détaché') ? 'block' : 'none';
}
</script>

</body>
</html>