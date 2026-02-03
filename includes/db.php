<?php
// --- CONFIGURATION TIDB CLOUD ---

// Remplace ces valeurs par celles de ta console TiDB
$host = 'gateway01.eu-central-1.prod.aws.tidbcloud.com'; // Ton Host TiDB
$port = '4000'; // Port standard TiDB
$dbname = 'car_dealership'; // Le nom de ta base
$user = 'WyjkLgtks66aSTW.root'; // Ton user complet
$pass = '400aCdOiGs5AwKsD'; // Ton mot de passe

try {
    // Configuration de la connexion sécurisée (SSL)
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Active SSL (Obligatoire pour TiDB Cloud)
        PDO::MYSQL_ATTR_SSL_CA => true,
        // Désactive la vérification stricte du certificat (évite les erreurs sur XAMPP/Windows)
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];

    // Création de la connexion PDO
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {
    // En cas d'erreur, on arrête tout et on affiche le message
    die("❌ Erreur de connexion TiDB : " . $e->getMessage());
}
?>