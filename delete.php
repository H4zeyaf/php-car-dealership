<?php include 'includes/security.php'; include 'includes/db.php';
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}
header("Location: admin.php");
?>