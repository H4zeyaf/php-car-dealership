<?php 
include 'includes/security.php'; 
include 'includes/db.php'; 

// --- 4. CHART DATA: REAL STATS (LAST 7 DAYS) ---

// 1. Prepare an array of the last 7 days (dates)
$dates = [];
$labels = []; 

for ($i = 6; $i >= 0; $i--) {
    $dateKey = date('Y-m-d', strtotime("-$i days"));
    $dates[$dateKey] = 0; // Initialize count to 0
    $labels[] = date('d/m', strtotime($dateKey));
}

// 2. Fetch Bookings count per day (CORRECTED: using created_at)
// We use created_at to track when the request was RECEIVED, not when the appointment is.
$sqlBookings = "SELECT DATE(created_at) as day, COUNT(*) as count 
                FROM bookings 
                WHERE created_at >= DATE(NOW()) - INTERVAL 7 DAY 
                GROUP BY DATE(created_at)";
$stmtB = $pdo->query($sqlBookings);

$bookingData = $dates; // Copy structure
while ($row = $stmtB->fetch()) {
    if (isset($bookingData[$row['day']])) {
        $bookingData[$row['day']] = $row['count'];
    }
}

// 3. Fetch Messages count per day
$sqlMessages = "SELECT DATE(created_at) as day, COUNT(*) as count 
                FROM messages 
                WHERE created_at >= DATE(NOW()) - INTERVAL 7 DAY 
                GROUP BY DATE(created_at)";
$stmtM = $pdo->query($sqlMessages);

$messageData = $dates;
while ($row = $stmtM->fetch()) {
    if (isset($messageData[$row['day']])) {
        $messageData[$row['day']] = $row['count'];
    }
}

// 4. Convert to JSON for JavaScript
$chartLabels = json_encode($labels);
$chartBookings = json_encode(array_values($bookingData));
$chartMessages = json_encode(array_values($messageData));

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

// --- 3. LOGIC: DELETE CLIENT ---
if (isset($_GET['delete_client'])) {
    $id = $_GET['delete_client'];
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin.php");
    exit();
}

// --- 4. LOGIC: DELETE SUBSCRIBER ---
if (isset($_GET['delete_subscriber'])) {
    $id = $_GET['delete_subscriber'];
    $stmt = $pdo->prepare("DELETE FROM subscribers WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin.php");
    exit();
}

// --- 5. LOGIC: DELETE MESSAGE ---
if (isset($_GET['delete_message'])) {
    $id = $_GET['delete_message'];
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin.php");
    exit();
}

// --- 6. FETCH GLOBAL STATS ---
$totalCars = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$totalSubscribers = $pdo->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Pro</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <div class="service-card" style="background:#8b5cf6; color:white;">
                <h2><?php echo $totalSubscribers; ?></h2>
                <p>Abonnés Newsletter</p>
            </div>
        </div>

        <div class="main-wrapper">
            <div class="content-area">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h2 style="color: #4b5563;"><i class="fas fa-car"></i> Gestion du Stock</h2>
        <a href="create.php" class="btn btn-green"><i class="fas fa-plus"></i> Nouvelle Voiture</a>
    </div>
    
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
        <table>
            <thead style="background: #e2e8f0; color: #333;">
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
                $cars = $pdo->query("SELECT * FROM cars ORDER BY id DESC");
                while($c = $cars->fetch()) {
                    $imgSource = !empty($c['image']) ? "uploads/".$c['image'] : "https://via.placeholder.com/50";
                    echo "<tr>
                        <td><img src='{$imgSource}' class='car-thumb'></td>
                        <td><strong>{$c['make']} {$c['model']}</strong></td>
                        <td>{$c['year']}</td>
                        <td>".number_format($c['price'], 0, ',', ' ')." DH</td>
                        <td>
                            <a href='edit.php?id={$c['id']}' class='btn-sm btn-blue action-btn'><i class='fas fa-edit'></i></a>
                            <a href='admin.php?delete_car={$c['id']}' class='btn-sm btn-red action-btn' onclick='return confirm(\"Supprimer cette voiture ?\")' style='background:#ef4444;'><i class='fas fa-trash'></i></a>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <h2 style="color: #4b5563;"><i class="fas fa-calendar-check"></i> Rendez-vous</h2>
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
        <table>
            <thead style="background: #e2e8f0; color: #333;">
                <tr>
                    <th>Client</th>
                    <th>Voiture</th>
                    <th>Date</th>
                    <th>Statut</th> 
                </tr>
            </thead>
            <tbody>
                <?php
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
    </div>

    <h2 style="color: #4b5563;"><i class="fas fa-envelope"></i> Derniers Messages</h2>
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
        <table>
            <thead style="background: #e2e8f0; color: #333;">
                <tr>
                    <th>Nom</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $msgs = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 10");
                while($m = $msgs->fetch()) {
                    $messagePreview = substr($m['message'], 0, 60);
                    $fullMessage = htmlspecialchars($m['message'], ENT_QUOTES);
                    $needsExpand = strlen($m['message']) > 60;
                    
                    echo "<tr>
                        <td>{$m['name']}</td>
                        <td>
                            <span class='msg-preview-{$m['id']}'>$messagePreview" . ($needsExpand ? "..." : "") . "</span>
                            <span class='msg-full-{$m['id']}' style='display:none;'>$fullMessage</span>";
                    
                    if($needsExpand) {
                        echo "<br><button onclick='toggleMessage({$m['id']})' class='btn-sm' style='margin-top:5px; padding:4px 10px; font-size:0.75rem; background:#6b7280; color:white; border:none; cursor:pointer; border-radius:4px;'>
                                <i class='fas fa-eye' id='icon-{$m['id']}'></i> <span id='text-{$m['id']}'>Voir plus</span>
                              </button>";
                    }
                    
                    echo "</td>
                        <td>".date('d/m/y', strtotime($m['created_at']))."</td>
                        <td>
                            <a href='admin.php?delete_message={$m['id']}' class='btn-sm btn-red' onclick='return confirm(\"Supprimer ce message ?\")' style='background:#ef4444;'>
                                <i class='fas fa-trash'></i>
                            </a>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
<h2 style="color: #4b5563;"><i class="fas fa-users"></i> Gestion des Clients</h2>
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
        <table>
            <thead style="background: #e2e8f0; color: #333;">
                <tr>
                    <th>ID</th>
                    <th>Nom Complet</th>
                    <th>Email</th>
                    <th>Date Inscription</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $clients = $pdo->query("SELECT * FROM clients ORDER BY created_at DESC");
                while($cl = $clients->fetch()) {
                    echo "<tr>
                        <td>#{$cl['id']}</td>
                        <td><strong>{$cl['full_name']}</strong></td>
                        <td>{$cl['email']}</td>
                        <td>".date('d/m/Y', strtotime($cl['created_at']))."</td>
                        <td style='white-space: nowrap;'>
                            <a href='edit_client.php?id={$cl['id']}' class='btn-sm btn-blue action-btn'><i class='fas fa-user-edit'></i></a>
                            <a href='admin.php?delete_client={$cl['id']}' class='btn-sm btn-red action-btn' onclick='return confirm(\"Supprimer ce client ? Tous ses rendez-vous et favoris seront également supprimés.\")' style='background:#ef4444;'><i class='fas fa-trash'></i></a>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <h2 style="color: #4b5563;"><i class="fas fa-envelope-open-text"></i> Abonnés à la Newsletter</h2>
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
        <table>
            <thead style="background: #e2e8f0; color: #333;">
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Date d'inscription</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $subscribers = $pdo->query("SELECT * FROM subscribers ORDER BY subscribed_at DESC");
                if($subscribers->rowCount() > 0) {
                    while($sub = $subscribers->fetch()) {
                        echo "<tr>
                            <td>#{$sub['id']}</td>
                            <td><i class='fas fa-envelope' style='color:#3b82f6; margin-right:8px;'></i>{$sub['email']}</td>
                            <td>".date('d/m/Y H:i', strtotime($sub['subscribed_at']))."</td>
                            <td>
                                <a href='admin.php?delete_subscriber={$sub['id']}' class='btn-sm btn-red' onclick='return confirm(\"Désinscrire cet abonné ?\")' style='background:#ef4444;'>
                                    <i class='fas fa-user-times'></i> Retirer
                                </a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center; color:#888; padding:20px;'>Aucun abonné pour le moment.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <h2 style="color: #4b5563;"><i class="fas fa-history"></i> Historique des Activités</h2>
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <table>
            <thead style="background: #e2e8f0; color: #333;">
                <tr>
                    <th>Action</th>
                    <th>Détails</th>
                    <th>IP</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch last 5 logs
                $logs = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5");
                
                while($log = $logs->fetch()) {
                    // Color code the actions
                    $badgeColor = '#64748b'; // Grey default
                    if($log['action'] == 'DELETE') $badgeColor = '#ef4444'; // Red
                    if($log['action'] == 'LOGIN') $badgeColor = '#10b981';  // Green
                    if($log['action'] == 'UPDATE') $badgeColor = '#3b82f6'; // Blue

                    echo "<tr>
                        <td><span style='background:$badgeColor; color:white; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:bold;'>{$log['action']}</span></td>
                        <td style='color:#334155;'>{$log['details']}</td>
                        <td style='font-family:monospace; color:#666;'>{$log['ip_address']}</td>
                        <td style='color:#666; font-size:0.85rem;'>".date('d/m H:i', strtotime($log['created_at']))."</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
            </div>

            <div class="sidebar">
                <h3>Statistiques</h3>
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>

 <script>
    function toggleMessage(id) {
        const preview = document.querySelector('.msg-preview-' + id);
        const full = document.querySelector('.msg-full-' + id);
        const icon = document.getElementById('icon-' + id);
        const text = document.getElementById('text-' + id);
        
        if (full.style.display === 'none') {
            preview.style.display = 'none';
            full.style.display = 'inline';
            icon.className = 'fas fa-eye-slash';
            text.textContent = 'Voir moins';
        } else {
            preview.style.display = 'inline';
            full.style.display = 'none';
            icon.className = 'fas fa-eye';
            text.textContent = 'Voir plus';
        }
    }

    const ctx = document.getElementById('myChart');

    // We inject the PHP JSON data directly into the JS variables
    const labels = <?php echo $chartLabels; ?>;
    const bookingData = <?php echo $chartBookings; ?>;
    const messageData = <?php echo $chartMessages; ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels, // ["05/01", "06/01", ...]
            datasets: [{
                label: 'Réservations (RDV)',
                data: bookingData, // [0, 1, 3, 0...]
                borderColor: '#10b981', // Green
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2
            },
            {
                label: 'Messages Reçus',
                data: messageData,
                borderColor: '#3b82f6', // Blue
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'Activité des 7 derniers jours'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1 // Force whole numbers (no 1.5 bookings)
                    }
                }
            }
        }
    });
</script>
</body>
</html>