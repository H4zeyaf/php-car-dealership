<?php include 'includes/security.php'; include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des données du formulaire
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    
    // Gestion de l'image
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);
    
    if(move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $sql = "INSERT INTO cars (make, model, year, price, description, image) VALUES (?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$make, $model, $year, $price, $desc, $image]);
        header("Location: admin.php"); // Redirection après succès
    } else {
        $error = "Erreur lors du téléchargement de l'image.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Ajouter un véhicule</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="form-container">
        <h2>Ajouter un nouveau véhicule</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Marque</label>
            <input type="text" name="make" placeholder="Ex: Dacia" required>
            
            <label>Modèle</label>
            <input type="text" name="model" placeholder="Ex: Logan" required>
            
            <label>Année</label>
            <input type="number" name="year" placeholder="Ex: 2023" required>
            
            <label>Prix (DH)</label>
            <input type="number" name="price" placeholder="Ex: 120000" required>
            
            <label>Description</label>
            <textarea name="description" placeholder="Détails du véhicule (kilométrage, options...)"></textarea>
            
            <label>Photo du véhicule</label>
            <input type="file" name="image" required>
            
            <button type="submit" class="btn btn-green">Enregistrer</button>
        </form>
    </div>
</body>
</html>