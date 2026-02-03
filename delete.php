<?php 
include 'includes/security.php'; 
include 'includes/db.php'; 
include 'includes/logger.php';

if (isset($_GET['id'])) {
    addLog($pdo, "DELETE", "Suppression de la voiture ID: $id");

    $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");

    $stmt->execute([$_GET['id']]);
}

header("Location: admin.php");

?>