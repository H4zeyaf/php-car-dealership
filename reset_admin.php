<?php
require 'includes/db.php';

// Le mot de passe que tu veux
$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // On vide la table users et on recrée l'admin proprement
    $pdo->exec("DELETE FROM users");
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES ('admin', ?)");
    $stmt->execute([$hash]);
    
    echo "✅ Succès ! Tu peux te connecter avec : <br>";
    echo "User: <strong>admin</strong><br>";
    echo "Pass: <strong>$password</strong>";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>