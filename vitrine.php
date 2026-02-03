<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/security_functions.php';

// 1. Initialisation des filtres (sanitized)
$make_filter = isset($_GET['make']) ? sanitizeInput($_GET['make']) : '';
$search_name = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$min_price   = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price   = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 4500000;
$max_limit   = 4500000;

// Validate price range
if ($min_price < 0) $min_price = 0;
if ($max_price > $max_limit) $max_price = $max_limit;
if ($min_price > $max_price) $min_price = 0;

// 2. Construction de la requête SQL
$sql = "SELECT * FROM cars WHERE 1=1";
$params = [];

if (!empty($make_filter)) {
    $sql .= " AND make = :make";
    $params[':make'] = $make_filter;
}

if (!empty($search_name)) {
    $sql .= " AND (LOWER(make) LIKE LOWER(:search) OR LOWER(model) LIKE LOWER(:search))";
    $params[':search'] = '%' . $search_name . '%';
}

$sql .= " AND price >= :min_price AND price <= :max_price";
$params[':min_price'] = $min_price;
$params[':max_price'] = $max_price;

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Récupération des marques pour le menu déroulant
$makes_stmt = $pdo->query("SELECT DISTINCT make FROM cars ORDER BY make ASC");
$makes = $makes_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vitrine - MHB Automobiles</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<div id="compareDock" style="position:fixed; bottom:-100px; left:0; width:100%; background:rgba(0,0,0,0.9); padding:15px; z-index:9999; display:flex; justify-content:center; align-items:center; gap:20px; transition:bottom 0.4s; border-top:4px solid var(--accent); backdrop-filter:blur(5px);">
    <h3 style="margin:0; color:white; font-size:1.1rem;">
        Comparateur <span id="compareCount" style="background:var(--accent); padding:2px 8px; border-radius:10px; font-size:0.9rem;">0</span>/3
    </h3>
    <a href="compare.php" class="btn btn-blue" style="padding:8px 20px;">
        VOIR LE COMPARATIF <i class="fas fa-arrow-right"></i>
    </a>
    <button onclick="clearCompare()" style="background:transparent; border:none; color:#999; cursor:pointer; text-decoration:underline;">Vider</button>
</div>

<script>
    function toggleCompare(id, btn) {
        // Simple visual toggle (optional)
        btn.innerHTML = '<i class="fas fa-check"></i> Ajouté';
        btn.style.background = '#e2e8f0';

        // AJAX Request
        let formData = new FormData();
        formData.append('action', 'add');
        formData.append('id', id);

        fetch('compare_handler.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(count => {
            updateDock(count);
        });
    }

    function clearCompare() {
        let formData = new FormData();
        formData.append('action', 'clear');
        fetch('compare_handler.php', { method: 'POST', body: formData })
        .then(() => {
            updateDock(0);
            // Reset buttons (Reload page effectively clears visuals)
            location.reload(); 
        });
    }

    function updateDock(count) {
        document.getElementById('compareCount').innerText = count;
        let dock = document.getElementById('compareDock');
        if(count > 0) {
            dock.style.bottom = "0px"; // Show dock
        } else {
            dock.style.bottom = "-100px"; // Hide dock
        }
    }
    /* --- FIX: CHECK STATUS ON LOAD --- */
    document.addEventListener("DOMContentLoaded", function() {
        // Create a dummy request just to get the current count
        let formData = new FormData();
        formData.append('action', 'check_status'); // This action does nothing in PHP, so it just returns the count

        fetch('compare_handler.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(count => {
            // Force the dock to update immediately with the existing session data
            updateDock(parseInt(count));
        });
    });
</script>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="page-header">
        <h1>Showroom <span style="color:var(--accent);">MHB</span></h1>
        <p style="font-size: 1.2rem; opacity: 0.9;">Trouvez le véhicule qui correspond à votre ambition.</p>
    </div>

    <div class="container">
        <div class="main-wrapper">
            
            <aside class="sidebar">
                <h3 style="border-bottom: 3px solid var(--accent); padding-bottom:8px; margin-bottom: 15px; margin-top: 0;">
                    <i class="fas fa-sliders-h"></i> Filtrer
                </h3>
                
                <form action="vitrine.php" method="GET">
                    
                    <div style="margin-bottom: 15px;">
                        <label><i class="fas fa-search" style="margin-right:5px;"></i>Rechercher</label>
                        <input type="text" name="search" placeholder="Nom du véhicule..." value="<?= htmlspecialchars($search_name) ?>" style="width: 100%; padding: 10px; box-sizing: border-box;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label><i class="fas fa-car" style="margin-right:5px;"></i>Marque</label>
                        <select name="make">
                            <option value="">Toutes les marques</option>
                            <?php foreach($makes as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>" <?= $make_filter == $m ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label><i class="fas fa-dollar-sign" style="margin-right:5px;"></i>Budget (DH)</label>
                        <div class="wrapper-slider">
                            <div class="values">
                                <span id="range1">0</span> - <span id="range2">4500000</span>
                            </div>
                            <div class="container-slider">
                                <div class="slider-track"></div>
                                <input type="range" min="0" max="<?= $max_limit ?>" value="<?= $min_price ?>" id="slider-1" name="min_price" oninput="slideOne()">
                                <input type="range" min="0" max="<?= $max_limit ?>" value="<?= $max_price ?>" id="slider-2" name="max_price" oninput="slideTwo()">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-blue sidebar-btn" style="margin-bottom: 10px;">
                        Appliquer les filtres
                    </button>
                    <a href="vitrine.php" class="btn sidebar-btn" style="background: #4b5563; text-align:center;">
                        Réinitialiser
                    </a>
                </form>
            </aside>

            <main class="content-area">
                <h2 style="color: white; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 10px;">
                    Véhicules disponibles <span style="font-size: 0.6em; color: var(--accent); vertical-align: middle; background: rgba(0,0,0,0.5); padding: 2px 10px; border-radius: 20px;"><?= count($cars) ?></span>
                </h2>

                <div id="results-area">
                <?php if(count($cars) > 0): ?>
                    <div class="car-grid">
                        <?php foreach($cars as $car): ?>
                            <div class="car-card">
                                <?php if($car['sale_price'] > 0): ?>
                                    <div class="badge-promo">
                                        -<?= round((($car['price'] - $car['sale_price']) / $car['price']) * 100) ?>%
                                    </div>
                                <?php endif; ?>

                                <div style="height: 200px; overflow: hidden; position: relative;">
                                    <img src="uploads/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['make']) ?>">
                                </div>
                                
                                <div class="card-body">
                                    <h3 class="card-title"><?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?></h3>
                                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">
                                        Année <?= htmlspecialchars($car['year']) ?>
                                    </p>
                                    
                                    <div style="margin-top: auto;">
                                        <?php if($car['sale_price'] > 0): ?>
                                            <span class="old-price" style="text-decoration: line-through; color: #999; font-size: 0.9rem;"><?= number_format($car['price'], 0, ',', ' ') ?> DH</span>
                                            <div class="card-price" style="color: var(--accent);"><?= number_format($car['sale_price'], 0, ',', ' ') ?> DH</div>
                                        <?php else: ?>
                                            <div class="card-price"><?= number_format($car['price'], 0, ',', ' ') ?> DH</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="details.php?id=<?= $car['id'] ?>" class="btn btn-blue" style="width:100%; margin-top: 10px; box-sizing: border-box;">
                                        Voir la fiche
                                    </a>
                                    <button type="button" class="btn" onclick="toggleCompare(<?php echo $car['id']; ?>, this)" style="width:100%; margin-top:5px; background:white; color:#333; border:1px solid #ccc;">
                                    <i class="fas fa-exchange-alt"></i> Comparer
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:60px; background:rgba(255,255,255,0.9); border-radius:8px;">
                        <i class="fas fa-car-crash" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                        <h3 style="color: #333;">Aucun résultat</h3>
                        <p style="color: #666;">Essayez d'ajuster vos critères de budget ou de marque.</p>
                        <a href="vitrine.php" class="btn btn-blue" style="margin-top: 20px;">Voir tout le stock</a>
                    </div>
                <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

<script>
    /* --- 1. EXISTING VISUAL LOGIC --- */
    let sliderOne = document.getElementById("slider-1");
    let sliderTwo = document.getElementById("slider-2");
    let displayValOne = document.getElementById("range1");
    let displayValTwo = document.getElementById("range2");
    let minGap = 50000;
    let sliderTrack = document.querySelector(".slider-track");
    let sliderMaxValue = document.getElementById("slider-1").max;

    // We create a variable to hold our timer
    let debounceTimer;

    function slideOne() {
        if (parseInt(sliderTwo.value) - parseInt(sliderOne.value) <= minGap) {
            sliderOne.value = parseInt(sliderTwo.value) - minGap;
        }
        displayValOne.textContent = parseInt(sliderOne.value).toLocaleString();
        fillColor();
        
        // OPTIMIZATION: Call the debounced search instead of direct search
        debouncedSearch();
    }

    function slideTwo() {
        if (parseInt(sliderTwo.value) - parseInt(sliderOne.value) <= minGap) {
            sliderTwo.value = parseInt(sliderOne.value) + minGap;
        }
        displayValTwo.textContent = parseInt(sliderTwo.value).toLocaleString();
        fillColor();
        
        // OPTIMIZATION: Call the debounced search
        debouncedSearch();
    }

    function fillColor() {
        let percent1 = (sliderOne.value / sliderMaxValue) * 100;
        let percent2 = (sliderTwo.value / sliderMaxValue) * 100;
        sliderTrack.style.background = `linear-gradient(to right, #d1d5db ${percent1}%, #e63946 ${percent1}%, #e63946 ${percent2}%, #d1d5db ${percent2}%)`;
    }

    /* --- 2. OPTIMIZED AJAX LOGIC (DEBOUNCE) --- */
    
    // This wrapper function handles the delay
    function debouncedSearch() {
        // Clear the previous timer if the user moves the slider again quickly
        clearTimeout(debounceTimer);
        
        // Set a new timer to run the actual search in 300ms
        debounceTimer = setTimeout(liveSearch, 300);
    }

    function liveSearch() {
        // Show a loading opacity to indicate work is happening
        let grid = document.getElementById('results-area');
        grid.style.opacity = '0.5'; 

        let make = document.querySelector('select[name="make"]').value;
        let search = document.querySelector('input[name="search"]').value;
        let minPrice = sliderOne.value;
        let maxPrice = sliderTwo.value;

        let url = `search_handler.php?make=${encodeURIComponent(make)}&search=${encodeURIComponent(search)}&min_price=${minPrice}&max_price=${maxPrice}`;

        fetch(url)
            .then(response => response.text())
            .then(data => {
                grid.innerHTML = data;
                grid.style.opacity = '1'; // Restore opacity
            })
            .catch(error => console.error('Error:', error));
    }

    /* --- 3. EVENT LISTENERS --- */
    window.onload = function() {
        slideOne();
        slideTwo();
        
        // For the dropdown and search input, we can search immediately (no debounce needed)
        document.querySelector('select[name="make"]').addEventListener('change', liveSearch);
        document.querySelector('input[name="search"]').addEventListener('input', debouncedSearch);
    };
</script>
</body>
</html>