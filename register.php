<?php
include 'includes/db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO clients (full_name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $pass]);
        header("Location: login_client.php?success=1");
    } catch (PDOException $e) { $error = "Cet email existe déjà."; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><title>Inscription</title><link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>"></head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="form-container">
        <h2>Créer un compte Client</h2>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Nom Complet" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit" class="btn btn-blue">S'inscrire</button>
        </form>
        <p>Déjà un compte ? <a href="login_client.php">Se connecter</a></p>
    </div>
</body>
</html>