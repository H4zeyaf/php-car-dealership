<?php
session_start();
include 'includes/db.php';

// 1. SECURITY: Redirect if not logged in
if (!isset($_SESSION['client_id'])) { 
    header("Location: login_client.php"); 
    exit(); 
}

$clientId = $_SESSION['client_id'];

// 2. FETCH CURRENT USER INFO (To pre-fill the settings form)
$stmtUser = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmtUser->execute([$clientId]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Use database name, fall back to session if needed
$clientName = $currentUser['full_name']; 

// --- LOGIC: REMOVE FROM WISHLIST ---
if (isset($_POST['remove_wishlist'])) {
    $stmtDel = $pdo->prepare("DELETE FROM wishlist WHERE client_id = ? AND car_id = ?");
    $stmtDel->execute([$clientId, $_POST['car_id']]);
    // Refresh to show changes
    header("Location: my_account.php#favoris"); 
    exit();
}

// --- LOGIC: CANCEL APPOINTMENT ---
if (isset($_POST['cancel_booking'])) {
    $b_id = $_POST['booking_id'];
    // Securely cancel: ensure the booking belongs to this client!
    $stmtC = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND client_id = ?");
    $stmtC->execute([$b_id, $clientId]);
    $successMsg = "Rendez-vous annulé avec succès.";
}

// --- LOGIC: UPDATE PROFILE ---
if (isset($_POST['update_profile'])) {
    $newName = htmlspecialchars($_POST['full_name']);
    $newEmail = htmlspecialchars($_POST['email']);
    $newPass = $_POST['password'];

    // Update Basic Info
    $sql = "UPDATE clients SET full_name = ?, email = ? WHERE id = ?";
    $params = [$newName, $newEmail, $clientId];

    // Update Password ONLY if the user typed one
    if (!empty($newPass)) {
        $sql = "UPDATE clients SET full_name = ?, email = ?, password = ? WHERE id = ?";
        $params = [$newName, $newEmail, password_hash($newPass, PASSWORD_DEFAULT), $clientId];
    }

    $stmtU = $pdo->prepare($sql);
    
    try {
        $stmtU->execute($params);
        $_SESSION['client_name'] = $newName; // Update session
        $clientName = $newName; // Update current variable
        
        // Refresh user data
        $stmtUser->execute([$clientId]);
        $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        $successMsg = "Profil mis à jour avec succès !";
    } catch (PDOException $e) {
        $errorMsg = "Erreur : Cet email est peut-être déjà utilisé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace - MHB</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="hero" style="padding: 60px 20px 30px; background: linear-gradient(to bottom, rgba(0,0,0,1) 0%, rgba(0, 0, 0, 0) 100%); margin-bottom: 0;">
        <h1 style="font-size: 2.5rem; margin-bottom: 10px;">
            Bonjour, <?php echo htmlspecialchars($clientName); ?> <i class="fas fa-hand-sparkles" style="color:var(--accent);"></i>
        </h1>
    </div>

    <div class="container" style="margin-top: 15px;">
        <div class="main-wrapper">
            
            <div class="sidebar" style="height: fit-content; text-align: left;"> 
                <div style="text-align:center; margin-bottom:20px;">
                    <div style="width:80px; height:80px; background:#e2e8f0; border-radius:50%; margin:0 auto 10px; display:flex; align-items:center; justify-content:center; font-size:2rem; color:#64748b;">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 style="margin:0; font-size:1.1rem; color:#333;"><?php echo htmlspecialchars($clientName); ?></h3>
                    <small style="color:#64748b;">Membre Client</small>
                </div>
                
                <hr style="margin: 20px 0; opacity:0.3;">
                
                <a href="#rdv" class="btn nav-btn" data-section="rdv" style="width:100%; box-sizing:border-box; background:white; color:#333; text-align:left; margin-bottom:10px; border:1px solid #eee; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-calendar-alt" style="color:var(--accent); width:20px; text-align:center;"></i> Mes Rendez-vous
                </a>
                <a href="#favoris" class="btn nav-btn" data-section="favoris" style="width:100%; box-sizing:border-box; background:white; color:#333; text-align:left; margin-bottom:10px; border:1px solid #eee; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-heart" style="color:var(--accent); width:20px; text-align:center;"></i> Mes Favoris
                </a>
                <a href="#settings" class="btn nav-btn" data-section="settings" style="width:100%; box-sizing:border-box; background:white; color:#333; text-align:left; margin-bottom:20px; border:1px solid #eee; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-cog" style="color:var(--accent); width:20px; text-align:center;"></i> Paramètres
                </a>

                <a href="logout.php" class="btn btn-red" style="width: 100%; box-sizing:border-box; justify-content:center;">
                    <i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Se déconnecter
                </a>
            </div>

            <div class="content-area">

                <?php if(isset($successMsg)): ?>
                    <div style="background:#d1fae5; color:#065f46; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #34d399;">
                        <i class="fas fa-check-circle"></i> <?php echo $successMsg; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($errorMsg)): ?>
                    <div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #f87171;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?>
                    </div>
                <?php endif; ?>

                <h2 id="rdv" style="border-bottom: 2px solid rgba(255,255,255,0.2); padding-bottom: 10px; margin-bottom: 20px; color: white;">
                    <i class="fas fa-calendar-check" style="margin-right:10px;"></i> Mes Rendez-vous
                </h2>
                <div id="rdv-content" style="margin-bottom: 50px;">
                    <!-- Desktop Table View -->
                    <div style="background: white; border:1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                        <table>
                            <thead style="background:#f8fafc;">
                                <tr><th>Voiture</th><th>Date</th><th>Statut</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT b.*, c.make, c.model FROM bookings b JOIN cars c ON b.car_id = c.id WHERE b.client_id = ? ORDER BY b.booking_date DESC";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$clientId]);
                                
                                if ($stmt->rowCount() > 0) {
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $statusColor = ($row['status'] == 'confirmed') ? '#10b981' : (($row['status'] == 'cancelled') ? '#ef4444' : '#f59e0b');
                                        
                                        // Translate status
                                        $statusText = match($row['status']) {
                                            'pending' => 'En attente',
                                            'confirmed' => 'Confirmé',
                                            'cancelled' => 'Annulé',
                                            default => $row['status']
                                        };

                                        echo "<tr>
                                            <td><strong>{$row['make']} {$row['model']}</strong></td>
                                            <td>".date('d/m/Y H:i', strtotime($row['booking_date']))."</td>
                                            <td><span style='background:$statusColor; color:white; padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:bold;'>$statusText</span></td>
                                            <td>";
                                            
                                            // Allow cancellation only if pending
                                            if($row['status'] == 'pending') {
                                                echo "<form method='POST' onsubmit='return confirm(\"Voulez-vous vraiment annuler ce rendez-vous ?\");'>
                                                    <input type='hidden' name='booking_id' value='{$row['id']}'>
                                                    <button type='submit' name='cancel_booking' class='btn-sm btn-red' style='border-radius:4px;'>Annuler</button>
                                                </form>";
                                            } else {
                                                echo "<span style='color:#ccc;'>-</span>";
                                            }
                                            
                                        echo "</td></tr>";
                                    }
                                } else { echo "<tr><td colspan='4' style='text-align:center; color:#888; padding:20px;'>Aucun rendez-vous pour le moment.</td></tr>"; }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <?php
                    $stmt->execute([$clientId]);
                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $statusColor = ($row['status'] == 'confirmed') ? '#10b981' : (($row['status'] == 'cancelled') ? '#ef4444' : '#f59e0b');
                            $statusText = match($row['status']) {
                                'pending' => 'En attente',
                                'confirmed' => 'Confirmé',
                                'cancelled' => 'Annulé',
                                default => $row['status']
                            };
                    ?>
                        <div class="appointment-card">
                            <h3><i class="fas fa-car" style="color:var(--accent); margin-right:8px;"></i><?php echo htmlspecialchars($row['make'] . ' ' . $row['model']); ?></h3>
                            
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-calendar"></i> Date</span>
                                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($row['booking_date'])); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-info-circle"></i> Statut</span>
                                <span class="status-badge" style="background:<?php echo $statusColor; ?>;"><?php echo $statusText; ?></span>
                            </div>
                            
                            <?php if($row['status'] == 'pending'): ?>
                            <form method='POST' onsubmit='return confirm("Voulez-vous vraiment annuler ce rendez-vous ?");'>
                                <input type='hidden' name='booking_id' value='<?php echo $row['id']; ?>'>
                                <button type='submit' name='cancel_booking' class='btn-sm btn-red'>
                                    <i class="fas fa-times"></i> Annuler le rendez-vous
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php
                        }
                    } else {
                        echo "<div class='appointment-card'><div class='empty-state'>
                            <i class='fas fa-calendar-times' style='font-size:2rem; color:#ccc; margin-bottom:10px; display:block;'></i>
                            Aucun rendez-vous pour le moment.
                        </div></div>";
                    }
                    ?>
                </div>

                <h2 id="favoris" style="border-bottom: 2px solid rgba(255,255,255,0.2); padding-bottom: 10px; margin-bottom: 20px; color: white;">
                    <i class="fas fa-heart" style="margin-right:10px;"></i> Mes Favoris
                </h2>
                <div id="favoris-content" class="car-grid" style="margin-bottom:50px;">
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
                                <h4 class="card-title"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></h4>
                                <div style="display:flex; gap:10px; margin-top:auto;">
                                    <a href="details.php?id=<?php echo $car['id']; ?>" class="btn btn-blue" style="flex:1; justify-content:center;">Voir</a>
                                    <form method="POST" style="flex:0;">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" name="remove_wishlist" class="btn btn-red" style="padding:12px 15px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php 
                        }
                    } else {
                        echo "<div style='grid-column:1/-1; text-align:center; padding:30px; background:#f9fafb; border-radius:8px; border:1px dashed #ccc;'>
                            <i class='fas fa-heart-broken' style='color:#ccc; font-size:2rem; margin-bottom:10px;'></i>
                            <p style='color:#666;'>Votre liste de favoris est vide.</p>
                            <a href='vitrine.php' class='btn btn-blue' style='margin-top:10px;'>Parcourir le catalogue</a>
                        </div>";
                    }
                    ?>
                </div>

                <h2 id="settings" style="border-bottom: 2px solid rgba(255,255,255,0.2); padding-bottom: 10px; margin-bottom: 20px; color: white;">
                    <i class="fas fa-cog" style="margin-right:10px;"></i> Paramètres du compte
                </h2>
                <div id="settings-content" style="background:white; border:1px solid #e2e8f0; padding:30px; border-radius:8px;">
                    <form method="POST">
                        <div style="display:flex; gap:20px; flex-wrap:wrap;">
                            <div style="flex:1; min-width:250px;">
                                <label style="display:block; margin-bottom:5px; font-weight:bold; color:#333;">Nom Complet</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                            </div>
                            <div style="flex:1; min-width:250px;">
                                <label style="display:block; margin-bottom:5px; font-weight:bold; color:#333;">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                            </div>
                        </div>

                        <label style="margin-top:20px; display:block; font-weight:bold; color:#333;">Nouveau mot de passe <small style="font-weight:normal; color:#666;">(Laisser vide pour ne pas changer)</small></label>
                        <input type="password" name="password" placeholder="••••••••" style="margin-bottom:20px;">

                        <button type="submit" name="update_profile" class="btn btn-blue">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    
    <?php if (file_exists('includes/footer.php')) include 'includes/footer.php'; ?>

    <script>
        // Mobile navigation toggle for sections
        if (window.innerWidth <= 768) {
            const navButtons = document.querySelectorAll('.nav-btn');
            
            navButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const sectionId = this.getAttribute('data-section');
                    
                    // Remove active class from all buttons
                    navButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Hide all sections
                    document.querySelectorAll('.content-area > h2[id]').forEach(section => {
                        section.classList.remove('active-section');
                    });
                    document.querySelectorAll('#rdv-content, #favoris-content, #settings-content').forEach(section => {
                        section.classList.remove('active-section');
                    });
                    
                    // Show the selected section and its content
                    if (sectionId === 'rdv') {
                        document.querySelector('h2#rdv').classList.add('active-section');
                        document.querySelector('#rdv-content').classList.add('active-section');
                    } else if (sectionId === 'favoris') {
                        document.querySelector('h2#favoris').classList.add('active-section');
                        document.querySelector('#favoris-content').classList.add('active-section');
                    } else if (sectionId === 'settings') {
                        document.querySelector('h2#settings').classList.add('active-section');
                        document.querySelector('#settings-content').classList.add('active-section');
                    }
                });
            });
            
            // Show first section by default
            if (navButtons.length > 0) {
                navButtons[0].click();
            }
        }
    </script>

</body>
</html>