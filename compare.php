<?php
session_start();
include 'includes/db.php';

// 1. Get IDs from Session
$ids = $_SESSION['compare_ids'] ?? [];

if (empty($ids)) {
    // If empty, redirect back to vitrine
    header("Location: vitrine.php");
    exit();
}

// 2. Fetch Cars from DB
// We use "FIND_IN_SET" or "IN" clause. IN is cleaner.
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM cars WHERE id IN ($placeholders)");
$stmt->execute($ids);
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Comparatif - MHB</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="hero" style="padding: 30px 20px 20px; background: linear-gradient(to bottom, rgba(0,0,0,1), rgba(0,0,0,0.5)); text-align: center;">
        <h1 style="margin-bottom: 10px;">Comparatif</h1>
    </div>

    <div class="container" style="margin-top: -10px;">
        <div style="overflow-x: auto;">
            <table class="compare-table">
                <tr>
                    <th>Véhicule</th>
                    <?php foreach($cars as $c): ?>
                    <td>
                        <button class="remove-btn" onclick="removeFromCompare(<?php echo $c['id']; ?>)">
                            <i class="fas fa-times"></i> Retirer
                        </button>
                        <img src="uploads/<?php echo htmlspecialchars($c['image']); ?>" class="compare-img">
                        <h3 style="margin: 10px 0 0; font-size:1.1rem;"><?php echo $c['make'] . ' ' . $c['model']; ?></h3>
                    </td>
                    <?php endforeach; ?>
                </tr>

                <tr>
                    <th>Prix</th>
                    <?php foreach($cars as $c): ?>
                    <td>
                        <div class="price-tag">
                            <?php 
                            $finalPrice = ($c['sale_price'] > 0) ? $c['sale_price'] : $c['price'];
                            echo number_format($finalPrice, 0, ',', ' '); 
                            ?> DH
                        </div>
                    </td>
                    <?php endforeach; ?>
                </tr>

                <tr>
                    <th>Année</th>
                    <?php foreach($cars as $c): ?>
                    <td><span style="background:#e2e8f0; padding:5px 10px; border-radius:4px; font-weight:bold;"><?php echo $c['year']; ?></span></td>
                    <?php endforeach; ?>
                </tr>

                <tr>
                    <th>Détails</th>
                    <?php foreach($cars as $c): ?>
                    <td style="color:#666; font-size:0.9rem; line-height:1.6; text-align:left;">
                        <?php echo nl2br(substr($c['description'], 0, 150)); ?>...
                    </td>
                    <?php endforeach; ?>
                </tr>

                <tr>
                    <th>Action</th>
                    <?php foreach($cars as $c): ?>
                    <td>
                        <a href="details.php?id=<?php echo $c['id']; ?>" class="compare-btn">
                            VOIR LA FICHE
                        </a>
                    </td>
                    <?php endforeach; ?>
                </tr>
            </table>
        </div>

        <div style="text-align:center; margin-top:30px;">
            <a href="vitrine.php" class="btn" style="background:white; color:#333; border:1px solid #ccc;">
                <i class="fas fa-plus"></i> Ajouter une autre voiture
            </a>
        </div>
    </div>

    <script>
        function removeFromCompare(id) {
            let formData = new FormData();
            formData.append('action', 'remove');
            formData.append('id', id);

            fetch('compare_handler.php', { method: 'POST', body: formData })
            .then(() => {
                location.reload(); // Refresh to update table
            });
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>