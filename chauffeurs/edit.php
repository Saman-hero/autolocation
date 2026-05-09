<?php
require_once "../config/database.php";
require_once "../models/ChauffeurModel.php";
require_once "../models/VehicleModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new ChauffeurModel($conn);

$vehicleModel = new VehicleModel($conn);
$vehicles = $vehicleModel->getFreeVehicles();

// ajouter le véhicule actuel s’il existe
if (!empty($c['vehicle_id'])) {
    $currentVehicle = $vehicleModel->getById($c['vehicle_id']);
    if ($currentVehicle) {
        $vehicles[] = $currentVehicle;
    }
}

$id = $_GET['id'];
$c = $model->getById($id);

if($_SERVER["REQUEST_METHOD"] == "POST") {

    $model->update([
        ":id" => $id,
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
<title>Modifier chauffeur</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

<h2>✏️ Modifier chauffeur</h2>

<form method="POST" class="row g-3">

<!-- Nom -->
<div class="col-md-6">
<label class="form-label">Nom</label>
<input name="nom" value="<?= $c['nom'] ?>" class="form-control">
</div>

<!-- Prénom -->
<div class="col-md-6">
<label class="form-label">Prénom</label>
<input name="prenom" value="<?= $c['prenom'] ?>" class="form-control">
</div>

<!-- Téléphone -->
<div class="col-md-6">
<label class="form-label">Téléphone</label>
<input name="telephone" value="<?= $c['telephone'] ?>" class="form-control">
</div>

<!-- Date -->
<div class="col-md-6">
<label class="form-label">Date embauche</label>
<input type="date" name="date_embauche" value="<?= $c['date_embauche'] ?>" class="form-control">
</div>

<!-- Grade -->
<div class="col-md-6">
<label class="form-label">Grade</label>
<select name="grade" class="form-select">
<option <?= $c['grade']=="adjudant-chef"?"selected":"" ?>>adjudant-chef</option>
<option <?= $c['grade']=="adjudant"?"selected":"" ?>>adjudant</option>
<option <?= $c['grade']=="sergent-chef"?"selected":"" ?>>sergent-chef</option>
<option <?= $c['grade']=="sergent"?"selected":"" ?>>sergent</option>
<option <?= $c['grade']=="caporal chef"?"selected":"" ?>>caporal chef</option>
<option <?= $c['grade']=="caporal"?"selected":"" ?>>caporal</option>
<option <?= $c['grade']=="1 classe"?"selected":"" ?>>1 classe</option>
<option <?= $c['grade']=="2 classe"?"selected":"" ?>>2 classe</option>
</select>
</div>

<!-- Niveau -->
<div class="col-md-6">
<label class="form-label">Niveau</label>
<select name="niveau" class="form-select">
<option <?= $c['niveau']=="débutant"?"selected":"" ?>>débutant</option>
<option <?= $c['niveau']=="moyen"?"selected":"" ?>>moyen</option>
<option <?= $c['niveau']=="professionnel"?"selected":"" ?>>professionnel</option>
</select>
</div>

<!-- Permis -->
<div class="col-md-6">
<label class="form-label">Type permis</label>
<input name="type_permis" value="<?= $c['type_permis'] ?>" class="form-control">
</div>

<!-- Véhicule -->
<div class="col-md-6">
<label class="form-label">Véhicule responsable</label>
<select name="vehicle_id" class="form-select">
    <option value="">-- Aucun véhicule --</option>
    <?php foreach($vehicles as $v): ?>
        <option value="<?= $v['id'] ?>" <?= ($c['vehicle_id']==$v['id'])?'selected':'' ?>>
            <?= $v['numero'] ?> - <?= $v['marque'] ?> <?= $v['modele'] ?>
        </option>
    <?php endforeach; ?>
</select>
</div>

<!-- CIN / MATRICULE -->
<div class="col-md-6">
<label class="form-label">Matricule</label>
<input name="matricule" value="<?= $c['matricule'] ?>" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">CIN</label>
<input name="cine" value="<?= $c['cine'] ?>" class="form-control">
</div>

<!-- Statut -->
<div class="col-md-6">
<label class="form-label">Statut</label>
<select name="statut" id="statut" class="form-select" onchange="toggleDet()">
<option <?= $c['statut']=="disponible"?"selected":"" ?>>disponible</option>
<option <?= $c['statut']=="en mission"?"selected":"" ?>>en mission</option>
<option <?= $c['statut']=="détaché"?"selected":"" ?>>détaché</option>
<option <?= $c['statut']=="congé"?"selected":"" ?>>congé</option>
</select>
</div>

<!-- Détachement -->
<div id="detachement" class="col-12" style="display:<?= $c['statut']=="détaché"?"block":"none" ?>">

<label class="form-label">Lieu détachement</label>
<input name="lieu_detachement" value="<?= $c['lieu_detachement'] ?>" class="form-control mb-2">

<label class="form-label">Date détachement</label>
<input type="date" name="date_detachement" value="<?= $c['date_detachement'] ?>" class="form-control">

</div>

<!-- Adresse -->
<div class="col-12">
<label class="form-label">Adresse</label>
<textarea name="adresse" class="form-control"><?= $c['adresse'] ?></textarea>
</div>

<!-- BTN -->
<div class="col-12">
<button class="btn btn-primary">Sauvegarder</button>
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