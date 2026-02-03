<?php
// edit_client.php
include 'includes/security.php';
include 'includes/db.php';
include 'includes/logger.php'; // For the Audit Log

// 1. CHECK ID
if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit();
}

$id = $_GET['id'];

// 2. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Update Basic Info
    $sql = "UPDATE clients SET full_name = ?, email = ? WHERE id = ?";
    $params = [$name, $email, $id];

    // If Admin typed a new password, hash it and update it
    if (!empty($password)) {
        $sql = "UPDATE clients SET full_name = ?, email = ?, password = ? WHERE id = ?";
        $params = [$name, $email, password_hash($password, PASSWORD_DEFAULT), $id];
        
        // Log specifically that password was changed
        addLog($pdo, "UPDATE", "Admin a modifié le mot de passe du client ID: $id");
    } else {
        addLog($pdo, "UPDATE", "Admin a modifié le profil du client ID: $id");
    }

    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute($params);
        header("Location: admin.php");
        exit();
    } catch (PDOException $e) {
        $error = "Erreur: Cet email est peut-être déjà utilisé.";
    }
}

// 3. FETCH EXISTING DATA
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if(!$client) die("Client introuvable");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Client - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background-color: #f1f5f9;">

    <?php include 'includes/navbar.php'; ?>

    <div class="container" style="max-width: 600px; margin-top: 50px;">
        
        <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h2 style="margin-top:0; color:#333; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                <i class="fas fa-user-edit" style="color:var(--accent);"></i> Modifier le Client
            </h2>

            <?php if(isset($error)): ?>
                <div style="background:#fee2e2; color:#991b1b; padding:10px; border-radius:4px; margin-bottom:15px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label>Nom Complet</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($client['full_name']); ?>" required>

                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>

                <div style="background: #f8fafc; padding: 15px; border: 1px dashed #cbd5e1; border-radius: 4px; margin: 20px 0;">
                    <label style="margin-top:0;">Réinitialiser le mot de passe</label>
                    <small style="display:block; color:#64748b; margin-bottom:10px;">Laisser vide pour ne pas changer le mot de passe actuel.</small>
                    <input type="password" name="password" placeholder="Nouveau mot de passe...">
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-blue" style="flex:1;">Enregistrer</button>
                    <a href="admin.php" class="btn btn-red" style="flex:1; text-align:center; background:#94a3b8;">Annuler</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>