<?php 
session_start();
include 'includes/db.php';

// 1. Récupération de l'ID de la voiture
$id = $_GET['id'] ?? null;
if (!$id) {
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
    if (isset($_SESSION['client_id'])) {
        try {
            $stmtW = $pdo->prepare("INSERT INTO wishlist (client_id, car_id) VALUES (?, ?)");
            $stmtW->execute([$_SESSION['client_id'], $id]);
            echo "<script>alert('✅ Véhicule ajouté à vos favoris !');</script>";
        } catch (PDOException $e) {
            echo "<script>alert('⚠️ Ce véhicule est déjà dans vos favoris.');</script>";
        }
    } else {
        header("Location: login_client.php"); // Redirection si pas connecté
        exit();
    }
}

// --- LOGIQUE RÉSERVATION (Test Drive) ---
if (isset($_POST['book_drive'])) {
    if (isset($_SESSION['client_id'])) {
        $date = $_POST['date'];
        $message = $_POST['message'];
        
        $stmtB = $pdo->prepare("INSERT INTO bookings (client_id, car_id, booking_date, message) VALUES (?, ?, ?, ?)");
        if($stmtB->execute([$_SESSION['client_id'], $id, $date, $message])) {
            echo "<script>alert('📅 Votre demande d\'essai a été envoyée avec succès ! Un conseiller vous contactera.');</script>";
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

                <div class="actions-area" style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    
                    <?php if (isset($_SESSION['client_id'])): ?>
                        <form method="POST" style="margin-bottom: 20px;">
                            <button type="submit" name="add_wishlist" class="btn btn-red" style="width: 100%; border-radius: 50px;">
                                <i class="fas fa-heart"></i> Ajouter à mes favoris
                            </button>
                        </form>

                        <div style="background: white; border: 2px solid var(--accent); padding: 25px; border-radius: 12px;">
                            <h3 style="color: var(--accent); margin-top: 0;"><i class="fas fa-calendar-check"></i> Réserver un essai</h3>
                            <form method="POST">
                                <label>Date et Heure souhaitée :</label>
                                <input type="datetime-local" name="date" required style="background: #f1f5f9;">
                                
                                <label>Message (Optionnel) :</label>
                                <textarea name="message" placeholder="Je voudrais tester la voiture sur autoroute..." rows="2"></textarea>
                                
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
    </div>

    <?php if (file_exists('includes/footer.php')) include 'includes/footer.php'; ?>

</body>
</html>