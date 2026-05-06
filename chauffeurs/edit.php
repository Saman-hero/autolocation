<?php
require_once "../config/database.php";
require_once "../models/ChauffeurModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new ChauffeurModel($conn);

$id = $_GET['id'];
$chauffeur = $model->getById($id);

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
<title>Edit chauffeur</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

<h2>✏️ Modifier chauffeur</h2>

<form method="POST" class="row g-3">

<input name="nom" value="<?= $chauffeur['nom'] ?>" class="form-control">
<input name="prenom" value="<?= $chauffeur['prenom'] ?>" class="form-control">
<input name="telephone" value="<?= $chauffeur['telephone'] ?>" class="form-control">
<input name="date_embauche" value="<?= $chauffeur['date_embauche'] ?>" type="date" class="form-control">
<input name="grade" value="<?= $chauffeur['grade'] ?>" class="form-control">
<input name="matricule" value="<?= $chauffeur['matricule'] ?>" class="form-control">
<input name="cine" value="<?= $chauffeur['cine'] ?>" class="form-control">

<select name="statut" class="form-select">
<option>disponible</option>
<option>en mission</option>
<option>congé</option>
</select>

<textarea name="adresse" class="form-control"><?= $chauffeur['adresse'] ?></textarea>

<button class="btn btn-success">Sauvegarder</button>

</form>

</div>

</body>
</html>