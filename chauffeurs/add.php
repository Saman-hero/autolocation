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
        ":statut" => $_POST['statut']
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

<div class="col-md-6">
<input name="nom" class="form-control" placeholder="Nom" required>
</div>

<div class="col-md-6">
<input name="prenom" class="form-control" placeholder="Prénom" required>
</div>

<div class="col-md-6">
<input name="telephone" class="form-control" placeholder="Téléphone">
</div>

<div class="col-md-6">
<input type="date" name="date_embauche" class="form-control">
</div>

<div class="col-md-6">
<input name="grade" class="form-control" placeholder="Grade">
</div>

<div class="col-md-6">
<input name="matricule" class="form-control" required placeholder="Matricule">
</div>

<div class="col-md-6">
<input name="cine" class="form-control" required placeholder="CIN">
</div>

<div class="col-md-6">
<select name="statut" class="form-select">
<option value="disponible">Disponible</option>
<option value="en mission">En mission</option>
<option value="congé">Congé</option>
</select>
</div>

<div class="col-12">
<textarea name="adresse" class="form-control" placeholder="Adresse"></textarea>
</div>

<div class="col-12">
<button class="btn btn-success">Ajouter</button>
</div>

</form>

</div>

</body>
</html>