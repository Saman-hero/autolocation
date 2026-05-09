<?php
require_once "../config/database.php";
require_once "../models/ChauffeurModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new ChauffeurModel($conn);

$filters = [
    "grade" => $_GET['grade'] ?? "",
    "statut" => $_GET['statut'] ?? "",
    "vehicle" => $_GET['vehicle'] ?? ""
];

$chauffeurs = $model->getAll($filters);
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

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">
<h3>🚛 Liste des chauffeurs</h3>

<div>
<a href="add.php" class="btn btn-success">+ Ajouter</a>
<a href="../vehicles/index.php" class="btn btn-primary">Véhicules</a>
<a href="../missions/index.php" class="btn btn-dark">Missions</a>
</div>
</div>

<!-- FILTERS -->
<form method="GET" class="row g-2 mb-3">

<div class="col-md-3">
<select name="grade" class="form-select">
<option value="">Tous les grades</option>
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

<div class="col-md-3">
<select name="statut" class="form-select">
<option value="">Tous les statuts</option>
<option>disponible</option>
<option>en mission</option>
<option>détaché</option>
<option>congé</option>
</select>
</div>

<div class="col-md-3">
<input name="vehicle" class="form-control" placeholder="ID véhicule">
</div>

<div class="col-md-3">
<button class="btn btn-primary w-100">Filtrer</button>
</div>

</form>

<!-- TABLE -->
<div class="card shadow-sm">
<div class="card-body table-responsive">

<table class="table table-striped table-hover align-middle">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Nom complet</th>
<th>Téléphone</th>
<th>Matricule</th>
<th>CIN</th>
<th>Grade</th>
<th>Niveau</th>
<th>Permis</th>
<th>Statut</th>
<th>Missions</th>
<th>Véhicule</th>
<th>Détachement</th>
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

<td><?= htmlspecialchars($c['niveau']) ?></td>

<td><?= htmlspecialchars($c['type_permis']) ?></td>

<!-- STATUT -->
<td>
<?php if($c['statut']=="disponible"): ?>
<span class="badge bg-success">Disponible</span>

<?php elseif($c['statut']=="en mission"): ?>
<span class="badge bg-warning text-dark">En mission</span>

<?php elseif($c['statut']=="détaché"): ?>
<span class="badge bg-info">Détaché</span>

<?php else: ?>
<span class="badge bg-danger"><?= $c['statut'] ?></span>
<?php endif; ?>
</td>

<!-- MISSIONS -->
<td>
<span class="badge bg-dark"><?= $c['total_missions'] ?? 0 ?></span>
</td>

<!-- VEHICLE -->
<td>
<?php if(!empty($c['vehicle_numero'])): ?>
🚗 <strong><?= $c['vehicle_numero'] ?></strong><br>
<small><?= $c['vehicle_marque'].' '.$c['vehicle_modele'] ?></small>
<?php else: ?>
<span class="text-muted">Aucun</span>
<?php endif; ?>
</td>

<!-- DETACHEMENT -->
<td>
<?php if($c['statut']=="détaché"): ?>
📍 <?= htmlspecialchars($c['lieu_detachement']) ?><br>
📅 <?= htmlspecialchars($c['date_detachement']) ?>
<?php else: ?>
<span class="text-muted">---</span>
<?php endif; ?>
</td>

<!-- ACTIONS -->
<td>

<a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">
✏️
</a>

<a href="delete.php?id=<?= $c['id'] ?>"
   onclick="return confirm('Supprimer ce chauffeur ?')"
   class="btn btn-sm btn-danger">
🗑️
</a>

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