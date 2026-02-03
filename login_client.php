<?php
session_start();
include 'includes/db.php';
include 'includes/security_functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF_FAILED', ['page' => 'login_client.php']);
        $error = "Requête invalide. Veuillez réessayer.";
    } else {
        // Rate Limiting
        $clientIP = getClientIP();
        $rateCheck = checkRateLimit('client_login_' . $clientIP, 5, 300);
        
        if (!$rateCheck['allowed']) {
            $waitMinutes = ceil($rateCheck['wait_seconds'] / 60);
            logSecurityEvent('RATE_LIMIT_EXCEEDED', ['ip' => $clientIP, 'page' => 'login_client.php']);
            $error = "Trop de tentatives. Réessayez dans $waitMinutes minutes.";
        } else {
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            
            if (!validateEmail($email)) {
                $error = "Adresse email invalide.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
                $stmt->execute([$email]);
                $client = $stmt->fetch();

                if ($client && password_verify($password, $client['password'])) {
                    // Reset rate limit on successful login
                    unset($_SESSION['rate_limit']['rl_client_login_' . $clientIP]);
                    
                    $_SESSION['client_id'] = $client['id'];
                    $_SESSION['client_name'] = $client['full_name'];
                    
                    // Regenerate session ID
                    session_regenerate_id(true);
                    
                    logSecurityEvent('CLIENT_LOGIN_SUCCESS', ['email' => $email]);
                    
                    header("Location: my_account.php");
                    exit();
                } else { 
                    logSecurityEvent('CLIENT_LOGIN_FAILED', ['email' => $email, 'ip' => $clientIP]);
                    $error = "Identifiants incorrects."; 
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><title>Connexion Client</title><link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="form-container">
        <h2>Connexion Client</h2>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required autocomplete="current-password">
            <button type="submit" class="btn btn-blue">Se connecter</button>
        </form>
        <p>Pas de compte ? <a href="register.php">S'inscrire</a></p>
    </div>
</body>
</html>