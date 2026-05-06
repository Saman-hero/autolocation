<?php
require_once "../config/database.php";
require_once "../models/MissionModel.php";

$db = new Database();
$conn = $db->getConnection();
$model = new MissionModel($conn);

$missions = $model->getAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des missions</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">📦 Liste des missions</h2>

        <a href="add.php" class="btn btn-success">
            + Ajouter mission
        </a>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Référence</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($missions as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['id']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($m['reference']) ?></strong>
                            </td>
                            <td>
                                <?php if($m['statut'] == "en cours"): ?>
                                    <span class="badge bg-warning text-dark">En cours</span>
                                <?php elseif($m['statut'] == "terminée"): ?>
                                    <span class="badge bg-success">Terminée</span>
                                <?php elseif($m['statut'] == "annulée"): ?>
                                    <span class="badge bg-danger">Annulée</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($m['statut']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="d-flex gap-1">
                                <a href="edit.php?id=<?= $m['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    ✏️ Edit
                                </a>
                                <a href="delete.php?id=<?= $m['id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Supprimer cette mission ?')">
                                    🗑️ Delete
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>