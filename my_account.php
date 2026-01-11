<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['client_id'])) { header("Location: login_client.php"); exit(); }

$clientId = $_SESSION['client_id'];
$clientName = $_SESSION['client_name'];

if (isset($_POST['remove_wishlist'])) {
    $stmtDel = $pdo->prepare("DELETE FROM wishlist WHERE client_id = ? AND car_id = ?");
    $stmtDel->execute([$clientId, $_POST['car_id']]);
    header("Location: my_account.php"); exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Espace - MHB</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="hero" style="padding: 40px 20px; background: linear-gradient(#1e293b, #0f172a);">
        <h1>Bonjour, <?php echo htmlspecialchars($clientName); ?> 👋</h1>
        <p>Bienvenue dans votre espace personnel.</p>
    </div>

    <div class="container" style="margin-top: 40px;">
        <div class="main-wrapper">
            
            <div class="sidebar" style="height: fit-content; text-align: center;"> <h3 style="margin-top:0;">Mon Profil</h3>
                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($clientName); ?></p>
                <hr>
                <a href="logout.php" class="btn btn-red" style="width: auto; display: inline-block; padding: 5px 15px;">
                    Se déconnecter
                </a>
            </div>

            <div class="content-area">

                <h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">📅 Mes Rendez-vous</h2>
                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 50px;">
                    <table>
                        <thead>
                            <tr><th>Voiture</th><th>Date</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT b.*, c.make, c.model FROM bookings b JOIN cars c ON b.car_id = c.id WHERE b.client_id = ? ORDER BY b.booking_date DESC";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$clientId]);
                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $statusColor = ($row['status'] == 'confirmed') ? '#10b981' : (($row['status'] == 'cancelled') ? '#ef4444' : '#f59e0b');
                                    echo "<tr><td>{$row['make']} {$row['model']}</td><td>".date('d/m/Y H:i', strtotime($row['booking_date']))."</td>
                                    <td><span style='background:$statusColor; color:white; padding:2px 8px; border-radius:10px; font-size:0.8rem;'>{$row['status']}</span></td></tr>";
                                }
                            } else { echo "<tr><td colspan='3' style='text-align:center; color:#888;'>Aucun rendez-vous.</td></tr>"; }
                            ?>
                        </tbody>
                    </table>
                </div>

                <h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">❤️ Mes Favoris</h2>
                <div class="car-grid">
                    <?php
                    $sqlW = "SELECT c.* FROM wishlist w JOIN cars c ON w.car_id = c.id WHERE w.client_id = ?";
                    $stmtW = $pdo->prepare($sqlW);
                    $stmtW->execute([$clientId]);

                    if ($stmtW->rowCount() > 0) {
                        while ($car = $stmtW->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                        <div class="car-card">
                            <div style="height: 180px; overflow: hidden;"><img src="uploads/<?php echo htmlspecialchars($car['image']); ?>" alt="Car"></div>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></h3>
                                <div style="display:flex; gap:10px; margin-top:auto;">
                                    <a href="details.php?id=<?php echo $car['id']; ?>" class="btn btn-blue" style="flex:1; text-align:center;">Voir</a>
                                    <form method="POST" style="flex:1;">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" name="remove_wishlist" class="btn btn-red" style="width:100%;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php 
                        }
                    } else {
                        echo "<p style='color:#666; width:100%; text-align:center;'>Aucun favori.</p>";
                    }
                    ?>
                </div>

                <?php if ($stmtW->rowCount() == 0): ?>
                    <a href="vitrine.php" class="btn btn-blue" style="display: block; margin: 30px auto; width: fit-content;">Parcourir le catalogue</a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>