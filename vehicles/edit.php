<?php
require_once "../config/database.php";
require_once "../models/VehicleModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new VehicleModel($conn);

$id = $_GET['id'];
$v = $model->getById($id);

if($_SERVER["REQUEST_METHOD"] == "POST") {

    $model->update([
        ":id" => $id,
        ":numero" => $_POST['numero'],
        ":marque" => $_POST['marque'],
        ":modele" => $_POST['modele'],
        ":type" => $_POST['type'],
        ":annee" => $_POST['annee'],
        ":kilometrage" => $_POST['kilometrage'],
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
    <title>Modifier véhicule</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">

    <div class="card shadow">
        <div class="card-body">

            <h2 class="mb-4">✏️ Modifier véhicule</h2>

            <form method="POST" class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Numéro</label>
                    <input name="numero" class="form-control"
                           value="<?= htmlspecialchars($v['numero']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Marque</label>
                    <input name="marque" class="form-control"
                           value="<?= htmlspecialchars($v['marque']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Modèle</label>
                    <input name="modele" class="form-control"
                           value="<?= htmlspecialchars($v['modele']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">

                        <option <?= $v['type']=="VIP"?"selected":"" ?>>VIP</option>
                        <option <?= $v['type']=="camion"?"selected":"" ?>>camion</option>
                        <option <?= $v['type']=="transport personnel"?"selected":"" ?>>
                            transport personnel
                        </option>

                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Année</label>
                    <input name="annee" class="form-control"
                           value="<?= htmlspecialchars($v['annee']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kilométrage</label>
                    <input name="kilometrage" class="form-control"
                           value="<?= htmlspecialchars($v['kilometrage']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">

                        <option <?= $v['statut']=="disponible"?"selected":"" ?>>
                            disponible
                        </option>

                        <option <?= $v['statut']=="maintenance"?"selected":"" ?>>
                            maintenance
                        </option>

                        <option <?= $v['statut']=="en mission"?"selected":"" ?>>
                            en mission
                        </option>

                    </select>
                </div>

                <div class="col-12 d-flex justify-content-between mt-3">

                    <a href="index.php" class="btn btn-secondary">
                        ← Retour
                    </a>

                    <button type="submit" class="btn btn-primary">
                        💾 Modifier véhicule
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>