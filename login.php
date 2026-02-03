<?php
session_start(); // Toujours démarrer la session au début
include 'includes/db.php';
include 'includes/security_functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF_FAILED', ['page' => 'login.php']);
        $error = "Requête invalide. Veuillez réessayer.";
    } else {
        // Rate Limiting
        $clientIP = getClientIP();
        $rateCheck = checkRateLimit('login_' . $clientIP, 5, 300); // 5 attempts per 5 minutes
        
        if (!$rateCheck['allowed']) {
            $waitMinutes = ceil($rateCheck['wait_seconds'] / 60);
            logSecurityEvent('RATE_LIMIT_EXCEEDED', ['ip' => $clientIP, 'page' => 'login.php']);
            $error = "Trop de tentatives. Veuillez réessayer dans $waitMinutes minutes.";
        } else {
            // Sanitize inputs
            $username = sanitizeInput($_POST['username']);
            $password = $_POST['password']; // Don't sanitize password (could alter it)

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérification du mot de passe crypté
            if ($user && password_verify($password, $user['password'])) {
                // Reset rate limit on successful login
                unset($_SESSION['rate_limit']['rl_login_' . $clientIP]);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'admin';
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                include 'includes/logger.php';
                addLog($pdo, "LOGIN", "Connexion réussie de l'admin: $username");
                logSecurityEvent('LOGIN_SUCCESS', ['username' => $username]);
                
                header("Location: admin.php");
                exit();
            } else {
                logSecurityEvent('LOGIN_FAILED', ['username' => $username, 'ip' => $clientIP]);
                $error = "Identifiants incorrects !";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <?php echo csrfField(); ?>
            
            <label>Nom d'utilisateur</label>
            <input type="text" name="username" required autocomplete="username">
            
            <label>Mot de passe</label>
            <input type="password" name="password" required autocomplete="current-password">
            
            <button type="submit" class="btn btn-blue">Se connecter</button>
        </form>
    </div>
</body>
</html>