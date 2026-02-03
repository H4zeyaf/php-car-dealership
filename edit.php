<?php
include 'includes/security.php';
include 'includes/db.php';

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->execute([$id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    
    // LOGIQUE PROMO (POURCENTAGE)
    $percent = $_POST['promo_percent']; // On récupère le chiffre (ex: 20)
    
    if ($percent > 0 && $percent < 100) {
        // Calcul : Prix - (Prix * Pourcentage / 100)
        $sale_price = $price - ($price * ($percent / 100));
    } else {
        $sale_price = 0; // Pas de promo
    }

    // Image logic
    $image = $car['image'];
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image);
    }

    // SQL UPDATE (J'ai ajouté sale_price ici, c'était ça le bug !)
    $sql = "UPDATE cars SET make=?, model=?, year=?, price=?, sale_price=?, description=?, image=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    
    // On sauvegarde
    if ($stmt->execute([$make, $model, $year, $price, $sale_price, $desc, $image, $id])) {
        header("Location: admin.php");
        exit();
    }
}

// Calcul du pourcentage actuel pour l'affichage (si une promo existe déjà)
$current_percent = 0;
if ($car['sale_price'] > 0 && $car['price'] > 0) {
    $current_percent = round((1 - ($car['sale_price'] / $car['price'])) * 100);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head><title>Modifier</title><link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>"></head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="form-container">
        <h2>Modifier le véhicule</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Marque</label>
            <input type="text" name="make" value="<?php echo $car['make']; ?>" required>
            
            <label>Modèle</label>
            <input type="text" name="model" value="<?php echo $car['model']; ?>" required>
            
            <label>Année</label>
            <input type="number" name="year" value="<?php echo $car['year']; ?>" required>
            
            <label>Prix Normal (DH)</label>
            <input type="number" name="price" value="<?php echo $car['price']; ?>" required>
            
            <label style="color: red; font-weight: bold;">Réduction en Pourcentage (%)</label>
            <p style="font-size: 0.8rem; color: #666; margin-top: -10px;">Mettez 0 pour annuler la promo.</p>
            <input type="number" name="promo_percent" value="<?php echo $current_percent; ?>" min="0" max="99" style="border-color: red;">
            
            <label>Description</label>
            <textarea name="description"><?php echo $car['description']; ?></textarea>
            
            <p>Image actuelle : <img src="uploads/<?php echo $car['image']; ?>" width="50"></p>
            <label>Nouvelle image (Optionnel)</label>
            <input type="file" name="image">
            
            <button type="submit" class="btn btn-blue">Mettre à jour</button>
        </form>
    </div>
</body>
</html>