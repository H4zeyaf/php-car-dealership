<?php
// includes/logger.php

function addLog($pdo, $action, $details) {
    // Only log if a user is logged in
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'];

        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    }
}
?>