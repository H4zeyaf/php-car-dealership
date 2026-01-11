<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch();

    if ($client && password_verify($password, $client['password'])) {
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $client['full_name'];
        header("Location: index.php");
        exit();
    } else { $error = "Identifiants incorrects."; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><title>Connexion Client</title><link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>"></head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="form-container">
        <h2>Connexion Client</h2>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit" class="btn btn-blue">Se connecter</button>
        </form>
        <p>Pas de compte ? <a href="register.php">S'inscrire</a></p>
    </div>
</body>
</html>