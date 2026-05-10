<?php
require_once "../config/database.php";

if ($_SESSION['user_role'] !== 'admin') {
    flash('danger', 'Accès réservé aux administrateurs.');
    header("Location: /location/index.php"); exit;
}

$id = (int)($_POST['id'] ?? 0);

// Cannot delete own account
if ($id === (int)$_SESSION['user_id']) {
    flash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
    header("Location: index.php"); exit;
}

if ($id) {
    $db   = new Database();
    $conn = $db->getConnection();
    $conn->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    flash('success', 'Utilisateur supprimé.');
}

header("Location: index.php"); exit;
