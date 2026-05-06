<?php
require_once "../config/database.php";
require_once "../models/VehicleModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new VehicleModel($conn);
$vehicles = $model->getAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des véhicules</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">🚚 Liste des véhicules</h2>
        <a href="add.php" class="btn btn-success">
            + Ajouter véhicule
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Numéro</th>
                            <th>Marque</th>
                            <th>Modèle</th>
                            <th>Type</th>
                            <th>Année</th>
                            <th>Kilométrage</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach($vehicles as $v): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><strong><?= $v['numero'] ?></strong></td>
                            <td><?= $v['marque'] ?></td>
                            <td><?= $v['modele'] ?></td>
                            <td><?= $v['type'] ?></td>
                            <td><?= $v['annee'] ?></td>
                            <td><?= $v['kilometrage'] ?> km</td>
                            <td>
                                <?php if($v['statut'] == "Disponible"): ?>
                                    <span class="badge bg-success">Disponible</span>
                                <?php elseif($v['statut'] == "En mission"): ?>
                                    <span class="badge bg-warning text-dark">En mission</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?= $v['statut'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-primary">
                                    Edit
                                </a>

                                <a href="delete.php?id=<?= $v['id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Supprimer ce véhicule ?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>

                </table>            

            </div>

        </div>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>