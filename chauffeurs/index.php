<?php
require_once "../config/database.php";
require_once "../models/ChauffeurModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new ChauffeurModel($conn);
$chauffeurs = $model->getAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Chauffeurs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

<div class="d-flex justify-content-between mb-3">
<h3>🚛 Liste des chauffeurs</h3>

<div>
<a href="add.php" class="btn btn-success">+ Ajouter</a>
<a href="../vehicles/index.php" class="btn btn-primary">Véhicules</a>
<a href="../missions/index.php" class="btn btn-dark">Missions</a>
</div>
</div>

<div class="card shadow-sm">
<div class="card-body">

<table class="table table-striped">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Nom</th>
<th>Téléphone</th>
<th>Matricule</th>
<th>CIN</th>
<th>Grade</th>
<th>Statut</th>
<th>Véhicule</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php foreach($chauffeurs as $c): ?>

<tr>
<td><?= $c['id'] ?></td>

<td><strong><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></strong></td>

<td><?= htmlspecialchars($c['telephone']) ?></td>
<td><?= htmlspecialchars($c['matricule']) ?></td>
<td><?= htmlspecialchars($c['cine']) ?></td>
<td><?= htmlspecialchars($c['grade']) ?></td>

<td>
<?php if($c['statut']=="disponible"): ?>
<span class="badge bg-success">Disponible</span>
<?php elseif($c['statut']=="en mission"): ?>
<span class="badge bg-warning text-dark">En mission</span>
<?php else: ?>
<span class="badge bg-danger"><?= $c['statut'] ?></span>
<?php endif; ?>
</td>

<td>
<?php if(!empty($c['vehicle_numero'])): ?>
🚗 <strong><?= htmlspecialchars($c['vehicle_numero']) ?></strong><br>
<small><?= htmlspecialchars($c['vehicle_marque'].' '.$c['vehicle_modele']) ?></small>
<?php else: ?>
<span class="text-danger">Aucun véhicule</span>
<?php endif; ?>
</td>

<td>
<a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">✏️</a>
<a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger"
onclick="return confirm('Supprimer ?')">🗑️</a>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>
</div>

</div>

</body>
</html>