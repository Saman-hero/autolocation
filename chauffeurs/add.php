<?php
require_once "../config/database.php";
require_once "../models/ChauffeurModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new ChauffeurModel($conn);

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

<!-- NOM -->
<div class="col-md-6">
<label class="form-label">Nom</label>
<input name="nom" class="form-control" placeholder="Nom" required>
</div>

<!-- PRENOM -->
<div class="col-md-6">
<label class="form-label">Prénom</label>
<input name="prenom" class="form-control" placeholder="Prénom" required>
</div>

<!-- TELEPHONE -->
<div class="col-md-6">
<label class="form-label">Téléphone</label>
<input name="telephone" class="form-control" placeholder="Téléphone">
</div>

<!-- DATE EMBauche -->
<div class="col-md-6">
<label class="form-label">Date d'embauche</label>
<input type="date" name="date_embauche" class="form-control">
</div>

<!-- GRADE -->
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

<!-- NIVEAU -->
<div class="col-md-6">
<label class="form-label">Niveau</label>
<select name="niveau" class="form-select">
<option>débutant</option>
<option>moyen</option>
<option>professionnel</option>
</select>
</div>

<!-- PERMIS -->
<div class="col-md-6">
<label class="form-label">Type permis</label>
<input name="type_permis" class="form-control" placeholder="Ex: B, C, D...">
</div>

<!-- VEHICULE -->
<div class="col-md-6">
<label class="form-label">Véhicule responsable (ID)</label>
<input name="vehicle_id" class="form-control" placeholder="Optionnel">
</div>

<!-- MATRICULE -->
<div class="col-md-6">
<label class="form-label">Matricule</label>
<input name="matricule" class="form-control" placeholder="Matricule" required>
</div>

<!-- CIN -->
<div class="col-md-6">
<label class="form-label">CIN</label>
<input name="cine" class="form-control" placeholder="CIN" required>
</div>

<!-- STATUT -->
<div class="col-md-6">
<label class="form-label">Statut</label>
<select name="statut" id="statut" class="form-select" onchange="toggleDet()">
<option value="disponible">disponible</option>
<option value="en mission">en mission</option>
<option value="détaché">détaché</option>
<option value="congé">congé</option>
</select>
</div>

<!-- DETACHEMENT -->
<div id="detachement" class="col-12" style="display:none">

<label class="form-label">Lieu de détachement</label>
<input name="lieu_detachement" class="form-control mb-2" placeholder="Lieu détachement">

<label class="form-label">Date de détachement</label>
<input type="date" name="date_detachement" class="form-control">

</div>

<!-- ADRESSE -->
<div class="col-12">
<label class="form-label">Adresse</label>
<textarea name="adresse" class="form-control" placeholder="Adresse"></textarea>
</div>

<!-- SUBMIT -->
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