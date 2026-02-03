<?php
session_start(); // ✅ 1. Start session immediately
include 'includes/db.php';
include 'includes/security_functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF_FAILED', ['page' => 'register.php']);
        $error = "Requête invalide. Veuillez réessayer.";
    } else {
        // Rate Limiting
        $clientIP = getClientIP();
        $rateCheck = checkRateLimit('register_' . $clientIP, 3, 600); // 3 registrations per 10 minutes
        
        if (!$rateCheck['allowed']) {
            $waitMinutes = ceil($rateCheck['wait_seconds'] / 60);
            logSecurityEvent('RATE_LIMIT_EXCEEDED', ['ip' => $clientIP, 'page' => 'register.php']);
            $error = "Trop de tentatives d'inscription. Réessayez dans $waitMinutes minutes.";
        } else {
            // Sanitize and validate inputs
            $name = sanitizeInput($_POST['name']);
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            
            // Validate email
            if (!validateEmail($email)) {
                $error = "Adresse email invalide.";
            }
            // Validate password strength
            elseif (strlen($password) < 8) {
                $error = "Le mot de passe doit contenir au moins 8 caractères.";
            }
            // Validate name
            elseif (strlen($name) < 2 || strlen($name) > 100) {
                $error = "Le nom doit contenir entre 2 et 100 caractères.";
            }
            else {
                $pass = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO clients (full_name, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $email, $pass]);
                    
                    // ✅ 2. Auto-Login Logic (Get the new ID and set session)
                    $newId = $pdo->lastInsertId();
                    $_SESSION['client_id'] = $newId;
                    $_SESSION['client_name'] = $name;
                    
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    logSecurityEvent('USER_REGISTERED', ['email' => $email, 'name' => $name]);
                    
                    // Send welcome email
                    include_once 'includes/email_config.php';
                    $subject = "Bienvenue chez MHB Automobiles";
                    $body = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                                .header { background: #000; color: white; padding: 20px; text-align: center; }
                                .content { background: white; padding: 30px; margin-top: 20px; border-radius: 8px; }
                                .highlight { color: #e63946; font-weight: bold; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1>MHB Automobiles</h1>
                                </div>
                                <div class='content'>
                                    <h2>Bienvenue " . escapeOutput($name) . " !</h2>
                                    <p>Merci d'avoir créé votre compte chez <span class='highlight'>MHB Automobiles</span>.</p>
                                    <p>Vous pouvez maintenant:</p>
                                    <ul>
                                        <li>Réserver des essais routiers</li>
                                        <li>Gérer vos favoris</li>
                                        <li>Suivre vos rendez-vous</li>
                                    </ul>
                                    <p>Explorez notre <a href='vitrine.php' style='color: #e63946;'>showroom</a> dès maintenant!</p>
                                    <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MHB Automobiles</strong></p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";
                    sendEmail($email, $subject, $body);

                    // ✅ 3. Redirect directly to dashboard
                    header("Location: my_account.php");
                    exit();

                } catch (PDOException $e) { 
                    logSecurityEvent('REGISTRATION_FAILED', ['email' => $email, 'error' => $e->getMessage()]);
                    $error = "Cet email existe déjà."; 
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><title>Inscription</title><link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>"></head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="form-container">
        <h2>Créer un compte Client</h2>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="text" name="name" placeholder="Nom Complet" required minlength="2" maxlength="100">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe (min 8 caractères)" required minlength="8">
            <button type="submit" class="btn btn-blue">S'inscrire</button>
        </form>
        <p>Déjà un compte ? <a href="login_client.php">Se connecter</a></p>
    </div>
</body>
</html>