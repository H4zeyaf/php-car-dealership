<?php 
include 'includes/security.php'; 
include 'includes/db.php'; 

// --- 1. LOGIC: UPDATE BOOKING STATUS ---
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['new_status'];

    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $booking_id]);
    
    // Refresh the page to show changes
    header("Location: admin.php"); 
    exit();
}

// --- 2. LOGIC: DELETE CAR (Optional but useful) ---
if (isset($_GET['delete_car'])) {
    $id = $_GET['delete_car'];
    $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin.php");
    exit();
}

// --- 3. FETCH GLOBAL STATS ---
$totalCars = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Dashboard Admin Pro</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Small inline fix for the car table image */
        .car-thumb { width: 50px; height: 40px; object-fit: cover; border-radius: 4px; }
        .action-btn { margin-right: 5px; text-decoration: none; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <h1>Tableau de Bord</h1>
        
        <div class="services-grid" style="margin-bottom: 40px;">
            <div class="service-card" style="background:#3b82f6; color:white;">
                <h2><?php echo $totalCars; ?></h2>
                <p>Voitures en stock</p>
            </div>
            <div class="service-card" style="background:#10b981; color:white;">
                <h2><?php echo $totalBookings; ?></h2>
                <p>Rendez-vous</p>
            </div>
            <div class="service-card" style="background:#f59e0b; color:white;">
                <h2><?php echo $totalMessages; ?></h2>
                <p>Messages reçus</p>
            </div>
        </div>

        <div class="main-wrapper">
            <div class="content-area">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h2>🚗 Gestion du Stock Complet</h2>
                    <a href="create.php" class="btn btn-green">+ Nouvelle Voiture</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Modèle</th>
                            <th>Année</th>
                            <th>Prix</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // UPDATED: Removed 'LIMIT 5' to show ALL cars
                        $cars = $pdo->query("SELECT * FROM cars ORDER BY id DESC");
                        
                        while($c = $cars->fetch()) {
                            // Check if image exists, otherwise use a placeholder
                            $imgSource = !empty($c['image']) ? "uploads/".$c['image'] : "https://via.placeholder.com/50";
                            
                            echo "<tr>
                                <td><img src='{$imgSource}' class='car-thumb'></td>
                                <td><strong>{$c['make']} {$c['model']}</strong></td>
                                <td>{$c['year']}</td>
                                <td>".number_format($c['price'], 0, ',', ' ')." DH</td>
                                <td>
                                    <a href='edit.php?id={$c['id']}' class='btn-sm btn-blue action-btn'><i class='fas fa-edit'></i></a>
                                    <a href='admin.php?delete_car={$c['id']}' class='btn-sm btn-red action-btn' onclick='return confirm(\"Supprimer cette voiture ?\")' style='background:#ef4444; color:white; padding:5px 10px; border-radius:4px;'><i class='fas fa-trash'></i></a>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <h2 style="margin-top:40px;">📅 Gestion des Rendez-vous</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Voiture</th>
                            <th>Date</th>
                            <th>Statut</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Increased Limit to 20 to see more history
                        $sql = "SELECT b.*, c.full_name, c.email, car.model 
                                FROM bookings b 
                                JOIN clients c ON b.client_id = c.id 
                                JOIN cars car ON b.car_id = car.id 
                                ORDER BY booking_date DESC LIMIT 20"; 
                        $books = $pdo->query($sql);
                        
                        while($b = $books->fetch()) {
                            $s_pending = ($b['status'] == 'pending') ? 'selected' : '';
                            $s_confirmed = ($b['status'] == 'confirmed') ? 'selected' : '';
                            $s_cancelled = ($b['status'] == 'cancelled') ? 'selected' : '';
                            
                            $rowColor = 'transparent';
                            if($b['status'] == 'confirmed') $rowColor = '#ecfdf5'; 
                            if($b['status'] == 'cancelled') $rowColor = '#fef2f2'; 

                            echo "<tr style='background:$rowColor;'>
                                <td>
                                    {$b['full_name']}<br>
                                    <small><a href='mailto:{$b['email']}' style='color:#3b82f6;'>{$b['email']}</a></small>
                                </td>
                                <td>{$b['model']}</td>
                                <td>".date('d/m/y H:i', strtotime($b['booking_date']))."</td>
                                
                                <td>
                                    <form method='POST' style='display:flex; align-items:center; gap:5px;'>
                                        <input type='hidden' name='booking_id' value='{$b['id']}'>
                                        <select name='new_status' style='padding:5px; border-radius:4px; border:1px solid #ccc;'>
                                            <option value='pending' $s_pending>En attente</option>
                                            <option value='confirmed' $s_confirmed>Confirmé</option>
                                            <option value='cancelled' $s_cancelled>Annulé</option>
                                        </select>
                                        <button type='submit' name='update_status' class='btn-sm btn-blue' style='padding:6px 10px; border:none; cursor:pointer;'>
                                            <i class='fas fa-check'></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <h2 style="margin-top:40px;">📩 Derniers Messages</h2>
                <table>
                    <thead><tr><th>Nom</th><th>Message</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php
                        $msgs = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 10");
                        while($m = $msgs->fetch()) {
                            echo "<tr>
                                <td>{$m['name']}</td>
                                <td>".substr($m['message'], 0, 60)."...</td>
                                <td>".date('d/m/y', strtotime($m['created_at']))."</td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>

            </div>

            <div class="sidebar">
                <h3>Statistiques</h3>
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('myChart');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Stock', 'RDV', 'Messages'],
            datasets: [{
                label: 'Activités',
                data: [<?php echo $totalCars; ?>, <?php echo $totalBookings; ?>, <?php echo $totalMessages; ?>],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                hoverOffset: 4
            }]
        }
    });
    </script>
</body>
</html>