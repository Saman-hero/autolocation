<?php
require_once "../config/database.php";
require_once "../models/VehicleModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new VehicleModel($conn);

// 🔍 récupération des filtres
$keyword = $_GET['keyword'] ?? '';
$type = $_GET['type'] ?? '';

// 🔍 recherche combinée
$vehicles = $model->search($keyword, $type);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des véhicules</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">🚚 Liste des véhicules</h2>

        <a href="add.php" class="btn btn-success">
            + Ajouter véhicule
        </a>
    </div>

    <!-- 🔍 SEARCH + FILTER -->
    <form method="GET" class="row g-2 mb-3">

        <!-- recherche -->
        <div class="col-md-4">
            <input type="text"
                   name="keyword"
                   class="form-control"
                   placeholder="🔍 Numéro, marque ou modèle"
                   value="<?= htmlspecialchars($keyword) ?>">
        </div>

        <!-- type -->
        <div class="col-md-3">
            <select name="type" class="form-select">

                <option value="">-- Tous les types --</option>

                <option value="VIP" <?= ($type=="VIP")?"selected":"" ?>>VIP</option>
                <option value="camion" <?= ($type=="camion")?"selected":"" ?>>Camion</option>
                <option value="transport personnel" <?= ($type=="transport personnel")?"selected":"" ?>>
                    Transport personnel
                </option>

            </select>
        </div>

        <!-- buttons -->
        <div class="col-md-5 d-flex gap-2">

            <button class="btn btn-primary">
                🔍 Rechercher
            </button>

            <a href="index.php" class="btn btn-secondary">
                Reset
            </a>

        </div>

    </form>

    <!-- TABLE -->
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

                    <?php if(empty($vehicles)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                Aucun véhicule trouvé
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach($vehicles as $v): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><strong><?= htmlspecialchars($v['numero']) ?></strong></td>
                            <td><?= htmlspecialchars($v['marque']) ?></td>
                            <td><?= htmlspecialchars($v['modele']) ?></td>
                            <td><?= htmlspecialchars($v['type']) ?></td>
                            <td><?= $v['annee'] ?></td>
                            <td><?= $v['kilometrage'] ?> km</td>

                            <!-- STATUT -->
                            <td>
                                <?php if($v['statut'] == "disponible"): ?>
                                    <span class="badge bg-success">Disponible</span>

                                <?php elseif($v['statut'] == "en mission"): ?>
                                    <span class="badge bg-warning text-dark">En mission</span>

                                <?php elseif($v['statut'] == "maintenance"): ?>
                                    <span class="badge bg-danger">Maintenance</span>

                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($v['statut']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- ACTIONS -->
                            <td class="d-flex gap-1">
                                <a href="edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-primary">
                                    ✏️ Edit
                                </a>

                                <a href="delete.php?id=<?= $v['id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Supprimer ce véhicule ?')">
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