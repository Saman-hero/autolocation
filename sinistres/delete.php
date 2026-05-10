<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $conn->prepare("DELETE FROM sinistres WHERE id=?")->execute([$id]);
    flash('success', 'Sinistre supprimé.');
}
header("Location: index.php");
exit;
