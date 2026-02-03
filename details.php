<?php 
session_start();
include 'includes/db.php';
include 'includes/security_functions.php';

// 1. Récupération de l'ID de la voiture (sanitized)
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$id || $id <= 0) {
    die("Véhicule introuvable.");
}

// 2. Récupération des infos de la voiture
$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->execute([$id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    die("Ce véhicule n'existe plus.");
}

// --- LOGIQUE WISHLIST (Ajout aux favoris) ---
if (isset($_POST['add_wishlist'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF_FAILED', ['page' => 'details.php', 'action' => 'add_wishlist']);
        echo "<script>alert('⚠️ Requête invalide.');</script>";
    } elseif (isset($_SESSION['client_id'])) {
        try {
            $stmtW = $pdo->prepare("INSERT INTO wishlist (client_id, car_id) VALUES (?, ?)");
            $stmtW->execute([$_SESSION['client_id'], $id]);
            logSecurityEvent('WISHLIST_ADDED', ['client_id' => $_SESSION['client_id'], 'car_id' => $id]);
            echo "<script>alert('✅ Véhicule ajouté à vos favoris !');</script>";
        } catch (PDOException $e) {
            echo "<script>alert('⚠️ Ce véhicule est déjà dans vos favoris.');</script>";
        }
    } else {
        header("Location: login_client.php");
        exit();
    }
}

// --- LOGIQUE RÉSERVATION (Test Drive) ---
if (isset($_POST['book_drive'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF_FAILED', ['page' => 'details.php', 'action' => 'book_drive']);
        echo "<script>alert('⚠️ Requête invalide.');</script>";
    } elseif (isset($_SESSION['client_id'])) {
        $date = sanitizeInput($_POST['date']);
        $message = sanitizeInput($_POST['message']);
        
        // Validate date format and future date
        if (!validateDate($date, 'Y-m-d\TH:i')) {
            echo "<script>alert('⚠️ Date invalide.');</script>";
        } elseif (strtotime($date) < time()) {
            echo "<script>alert('⚠️ La date doit être dans le futur.');</script>";
        } elseif (strlen($message) > 500) {
            echo "<script>alert('⚠️ Message trop long (max 500 caractères).');</script>";
        } else {
            $stmtB = $pdo->prepare("INSERT INTO bookings (client_id, car_id, booking_date, message) VALUES (?, ?, ?, ?)");
            if($stmtB->execute([$_SESSION['client_id'], $id, $date, $message])) {
                // Get client info for email
                $stmtClient = $pdo->prepare("SELECT full_name, email FROM clients WHERE id = ?");
                $stmtClient->execute([$_SESSION['client_id']]);
                $clientInfo = $stmtClient->fetch(PDO::FETCH_ASSOC);
                
                logSecurityEvent('BOOKING_CREATED', [
                    'client_id' => $_SESSION['client_id'], 
                    'car_id' => $id,
                    'date' => $date
                ]);
                
                // Send confirmation email
                include_once 'includes/email_config.php';
                sendAppointmentConfirmation(
                    $clientInfo['email'], 
                    $clientInfo['full_name'], 
                    $car['make'], 
                    $car['model'], 
                    date('d/m/Y à H:i', strtotime($date))
                );
                
                echo "<script>alert('📅 Votre demande d\'essai a été envoyée avec succès ! Un email de confirmation vous a été envoyé.');</script>";
            }
        }
    } else {
        header("Location: login_client.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?> - MHB</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container" style="margin-top: 40px;">
        
        <a href="vitrine.php" class="btn-sm" style="background:#64748b; color:white; display:inline-block; margin-bottom:20px;">
            <i class="fas fa-arrow-left"></i> Retour au Showroom
        </a>

        <div class="details-flex">
            
            <div style="flex: 1;">
                <div style="position: relative; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <?php if($car['sale_price'] > 0): ?>
                        <div class="badge-promo" style="font-size: 1.2rem; padding: 10px 20px;">PROMO</div>
                    <?php endif; ?>
                    
                    <img src="uploads/<?php echo htmlspecialchars($car['image']); ?>" alt="Car Image" style="width: 100%; display: block;">
                </div>
            </div>

            <div class="info" style="flex: 1;">
                <h1 style="margin-bottom: 5px; color: var(--primary);">
                    <?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>
                </h1>
                <p style="color: #64748b; margin-top: 0;">Année : <?php echo $car['year']; ?></p>

                <div style="margin: 20px 0;">
                    <?php if($car['sale_price'] > 0): ?>
                        <span class="old-price" style="font-size: 1.5rem;"><?php echo number_format($car['price'], 0, ',', ' '); ?> DH</span>
                        <span class="new-price" style="font-size: 2.5rem;"><?php echo number_format($car['sale_price'], 0, ',', ' '); ?> DH</span>
                    <?php else: ?>
                        <span class="new-price" style="font-size: 2.5rem; color: var(--primary);"><?php echo number_format($car['price'], 0, ',', ' '); ?> DH</span>
                    <?php endif; ?>
                </div>

                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
                    <h3 style="margin-top: 0;">Description</h3>
                    <p style="color: #334155; line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($car['description'])); ?>
                    </p>
                </div>
                <div style="background: #f1f5f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #cbd5e1;">
    <h3 style="margin-top:0; color:var(--primary);"><i class="fas fa-calculator"></i> Estimation Crédit</h3>
    
    <div style="display:flex; gap:10px; margin-bottom:10px; flex-wrap: wrap;">
        <div style="flex:1; min-width: 140px;">
            <label style="font-size:0.8rem; font-weight:bold;">Apport (DH)</label>
            <input type="number" id="down_payment" value="0" oninput="calculateLoan()" style="padding:8px; margin:0; width: 100%; box-sizing: border-box;">
        </div>
        <div style="flex:1; min-width: 140px;">
            <label style="font-size:0.8rem; font-weight:bold;">Durée (Mois)</label>
            <input type="number" id="months" value="60" oninput="calculateLoan()" style="padding:8px; margin:0; width: 100%; box-sizing: border-box;">
        </div>
        <div style="flex:1; min-width: 140px;">
            <label style="font-size:0.8rem; font-weight:bold;">Taux (%)</label>
            <input type="number" id="interest_rate" value="4.5" step="0.1" oninput="calculateLoan()" style="padding:8px; margin:0; width: 100%; box-sizing: border-box;">
        </div>
    </div>

    <div style="background:var(--primary); color:white; padding:15px; border-radius:4px; text-align:center;">
        <span style="font-size:0.9rem;">Mensualité Estimée</span><br>
        <strong id="monthly_payment" style="font-size:1.5rem; color:var(--accent);">0 DH</strong> / mois
    </div>

    <script>
        function calculateLoan() {
            // On récupère le prix final (Promo ou Normal)
            let price = <?php echo ($car['sale_price'] > 0) ? $car['sale_price'] : $car['price']; ?>;
            let downPayment = document.getElementById('down_payment').value;
            let months = document.getElementById('months').value;
            let rate = document.getElementById('interest_rate').value / 100 / 12;

            let principal = price - downPayment;
            
            if(principal <= 0) {
                document.getElementById('monthly_payment').innerText = "0 DH";
                return;
            }

            // Formule Mathématique Crédit
            let x = Math.pow(1 + rate, months);
            let monthly = (principal * x * rate) / (x - 1);

            if (!isFinite(monthly)) {
                document.getElementById('monthly_payment').innerText = "0 DH";
            } else {
                document.getElementById('monthly_payment').innerText = Math.round(monthly).toLocaleString() + " DH";
            }
        }
        // Lancer le calcul au chargement
        window.onload = calculateLoan;
    </script>
</div>
                <div class="actions-area" style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    
                    <?php if (isset($_SESSION['client_id'])): ?>
                        <form method="POST" style="margin-bottom: 20px;">
                            <?php echo csrfField(); ?>
                            <button type="submit" name="add_wishlist" class="btn btn-red" style="width: 100%; border-radius: 50px;">
                                <i class="fas fa-heart"></i> Ajouter à mes favoris
                            </button>
                            <a href="export_pdf.php?id=<?php echo $car['id']; ?>" class="btn" style="background: #333; color: white; width: 100%; margin-top: 10px;">
                            <i class="fas fa-file-pdf"></i> Télécharger la fiche technique
                            </a>
                        </form>

                        <div style="background: white; border: 2px solid var(--accent); padding: 25px; border-radius: 12px;">
                            <h3 style="color: var(--accent); margin-top: 0;"><i class="fas fa-calendar-check"></i> Réserver un essai</h3>
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <label>Date et Heure souhaitée :</label>
                                <input type="datetime-local" name="date" required style="background: #f1f5f9;" min="<?php echo date('Y-m-d\TH:i'); ?>">
                                
                                <label>Message (Optionnel) :</label>
                                <textarea name="message" placeholder="Je voudrais tester la voiture sur autoroute..." rows="2" maxlength="500"></textarea>
                                
                                <button type="submit" name="book_drive" class="btn btn-blue" style="width: 100%;">
                                    Confirmer le rendez-vous
                                </button>
                            </form>
                        </div>

                    <?php else: ?>
                        <div style="text-align: center; background: #f1f5f9; padding: 30px; border-radius: 12px;">
                            <i class="fas fa-lock" style="font-size: 2rem; color: #94a3b8; margin-bottom: 10px;"></i>
                            <h3>Connectez-vous pour réserver</h3>
                            <p>Pour ajouter ce véhicule à vos favoris ou réserver un essai, vous devez avoir un compte client.</p>
                            <a href="login_client.php" class="btn btn-blue">Se connecter / S'inscrire</a>
                        </div>
                    <?php endif; ?>

                </div>
                
                <div style="margin-top: 30px; text-align: center; color: #64748b;">
                    <p><i class="fas fa-phone"></i> Besoin d'infos immédiates ? Appelez le <strong>05 22 99 99 99</strong></p>
                </div>

            </div>
        </div>
        <div class="section section-light" style="background: white; padding-top: 50px; margin-top: 40px; border-radius: 12px; box-shadow: 0 -10px 30px rgba(0,0,0,0.05);">
        <div class="container">
            <h3 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 30px;">
                Ces véhicules pourraient vous intéresser
            </h3>
            
            <div class="car-grid">
                <?php
                // Logique : Même catégorie de prix (+ ou - 30%) ET pas la voiture actuelle
                $min_p = $car['price'] * 0.7;
                $max_p = $car['price'] * 1.3;
                
                $stmtSim = $pdo->prepare("SELECT * FROM cars WHERE id != ? AND price BETWEEN ? AND ? LIMIT 3");
                $stmtSim->execute([$id, $min_p, $max_p]);
                
                if($stmtSim->rowCount() > 0):
                    while($sim = $stmtSim->fetch(PDO::FETCH_ASSOC)):
                ?>
                    <div class="car-card">
                        <div style="height: 180px; overflow: hidden;">
                            <img src="uploads/<?php echo htmlspecialchars($sim['image']); ?>" alt="Car">
                        </div>
                        <div class="card-body">
                            <h4 class="card-title"><?php echo htmlspecialchars($sim['make'] . ' ' . $sim['model']); ?></h4>
                            <div style="margin-top:auto;">
                                <strong style="color:var(--primary); font-size:1.2rem;">
                                    <?php echo number_format($sim['price'], 0, ',', ' '); ?> DH
                                </strong>
                            </div>
                            <a href="details.php?id=<?php echo $sim['id']; ?>" class="btn btn-blue" style="width:100%; margin-top:10px; text-align:center;">
                                Voir
                            </a>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <p style="color:#666;">Aucun autre véhicule similaire trouvé pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div> 
    </div>

    <?php if (file_exists('includes/footer.php')) include 'includes/footer.php'; ?>

</body>
</html>