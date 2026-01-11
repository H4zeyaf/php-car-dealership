<?php
session_start(); // Toujours démarrer la session au début
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérification du mot de passe crypté
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: admin.php"); // Redirection vers l'admin
        exit();
    } else {
        $error = "Identifiants incorrects !";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Admin</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="form-container">
        <h2>Espace Administration</h2>
        <?php if(isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
        
        <form method="POST">
            <label>Nom d'utilisateur</label>
            <input type="text" name="username" required>
            
            <label>Mot de passe</label>
            <input type="password" name="password" required>
            
            <button type="submit" class="btn btn-blue">Se connecter</button>
        </form>
    </div>
</body>
</html>