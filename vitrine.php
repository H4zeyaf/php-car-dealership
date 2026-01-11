<?php
session_start();
require_once 'includes/db.php';

// 1. Initialisation des filtres
$make_filter = isset($_GET['make']) ? $_GET['make'] : '';
$min_price   = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price   = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 2000000;
$max_limit   = 2000000;

// 2. Construction de la requête SQL
$sql = "SELECT * FROM cars WHERE 1=1";
$params = [];

if (!empty($make_filter)) {
    $sql .= " AND make = :make";
    $params[':make'] = $make_filter;
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
    
    <style>
        /* --- STYLES SPÉCIFIQUES AU SLIDER DE PRIX --- */
        
        .wrapper-slider {
            width: 100%;
            padding: 15px 0;
            margin-bottom: 25px;
        }
        
        .values {
            text-align: center;
            font-weight: 800;
            color: var(--accent);
            margin-bottom: 10px;
            font-size: 1.1rem;
            font-style: italic;
        }
        
        .container-slider {
            position: relative;
            width: 100%;
            height: 40px;
        }
        
        /* CORRECTION SLIDER : La ligne s'arrête exactement sous les points */
        .slider-track {
            /* On retire 24px (largeur du point) pour que la ligne ne dépasse pas */
            width: calc(100% - 24px);
            height: 6px;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: #d1d5db;
            border-radius: 3px;
            z-index: 0;
            /* On décale de 12px (rayon du point) pour centrer */
            left: 12px; 
        }
        
        input[type="range"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            width: 100%;
            outline: none;
            position: absolute;
            margin: 0;
            top: 50%;
            transform: translateY(-50%);
            background-color: transparent;
            pointer-events: none;
            z-index: 1;
        }
        
        /* Style du bouton rond (Thumb) - Chrome/Safari */
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 24px;
            width: 24px;
            background-color: var(--accent);
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
            border: 3px solid #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            transition: transform 0.2s;
        }
        input[type="range"]::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }

        /* Style du bouton rond (Thumb) - Firefox */
        input[type="range"]::-moz-range-thumb {
            -webkit-appearance: none;
            height: 24px;
            width: 24px;
            background-color: var(--accent);
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
            border: 3px solid #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        /* Ajustement mise en page Vitrine */
        .page-header {
            text-align: center;
            padding: 60px 20px;
            color: white;
            /* --- MODIFICATION ICI : DÉGRADÉ NOIR DU HAUT VERS LE BAS --- */
            background: linear-gradient(to bottom, rgba(0,0,0,1) 0%, rgba(0, 0, 0, 0) 100%);
            margin-bottom: 20px; /* Petit espacement pour que le dégradé respire */
        }
        .page-header h1 {
            font-size: 3rem;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.8);
            margin-bottom: 10px;
        }
        
        .sidebar label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        /* CORRECTION BOUTONS SIDEBAR : Empêche le débordement */
        .sidebar-btn {
            width: 100%; 
            box-sizing: border-box; /* Inclut le padding dans la largeur */
            display: block; /* Assure que le bouton prend sa propre ligne */
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="page-header">
        <h1>Showroom <span style="color:var(--accent);">MHB</span></h1>
        <p style="font-size: 1.2rem; opacity: 0.9;">Trouvez le véhicule qui correspond à votre ambition.</p>
    </div>

    <div class="container">
        <div class="main-wrapper">
            
            <aside class="sidebar">
                <h3 style="border-bottom: 3px solid var(--accent); padding-bottom:10px; margin-bottom: 20px;">
                    <i class="fas fa-sliders-h"></i> Filtrer
                </h3>
                
                <form action="vitrine.php" method="GET">
                    
                    <div style="margin-bottom: 25px;">
                        <label>Marque</label>
                        <select name="make">
                            <option value="">Toutes les marques</option>
                            <?php foreach($makes as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>" <?= $make_filter == $m ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label>Budget (DH)</label>
                        <div class="wrapper-slider">
                            <div class="values">
                                <span id="range1">0</span> - <span id="range2">2M+</span>
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
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        let sliderOne = document.getElementById("slider-1");
        let sliderTwo = document.getElementById("slider-2");
        let displayValOne = document.getElementById("range1");
        let displayValTwo = document.getElementById("range2");
        let minGap = 50000;
        let sliderTrack = document.querySelector(".slider-track");
        let sliderMaxValue = document.getElementById("slider-1").max;

        function slideOne() {
            if (parseInt(sliderTwo.value) - parseInt(sliderOne.value) <= minGap) {
                sliderOne.value = parseInt(sliderTwo.value) - minGap;
            }
            displayValOne.textContent = parseInt(sliderOne.value).toLocaleString();
            fillColor();
        }
        function slideTwo() {
            if (parseInt(sliderTwo.value) - parseInt(sliderOne.value) <= minGap) {
                sliderTwo.value = parseInt(sliderOne.value) + minGap;
            }
            displayValTwo.textContent = parseInt(sliderTwo.value).toLocaleString();
            fillColor();
        }
        function fillColor() {
            let percent1 = (sliderOne.value / sliderMaxValue) * 100;
            let percent2 = (sliderTwo.value / sliderMaxValue) * 100;
            sliderTrack.style.background = `linear-gradient(to right, #d1d5db ${percent1}%, #e63946 ${percent1}%, #e63946 ${percent2}%, #d1d5db ${percent2}%)`;
        }
        
        window.onload = function() {
            slideOne();
            slideTwo();
        };
    </script>

</body>
</html>