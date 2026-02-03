<?php
// search_handler.php
require_once 'includes/db.php';

// 1. Retrieve filters
$make_filter = isset($_GET['make']) ? $_GET['make'] : '';
$search_name = isset($_GET['search']) ? $_GET['search'] : '';
$min_price   = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price   = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 100000000;

// 2. Build Query
$sql = "SELECT * FROM cars WHERE price >= :min_price AND price <= :max_price";
$params = [
    ':min_price' => $min_price,
    ':max_price' => $max_price
];

if (!empty($make_filter)) {
    $sql .= " AND make = :make";
    $params[':make'] = $make_filter;
}

if (!empty($search_name)) {
    $sql .= " AND (LOWER(make) LIKE LOWER(:search) OR LOWER(model) LIKE LOWER(:search))";
    $params[':search'] = '%' . $search_name . '%';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Generate HTML Output
if(count($cars) > 0) {
    echo "<div class='car-grid'>";
    foreach($cars as $car) {
        // Promo Logic
        $promoBadge = '';
        if($car['sale_price'] > 0) {
            $percent = round((($car['price'] - $car['sale_price']) / $car['price']) * 100);
            $promoBadge = "<div class='badge-promo'>-{$percent}%</div>";
        }

        // Price Logic
        if($car['sale_price'] > 0) {
            $priceDisplay = "<span class='old-price' style='text-decoration: line-through; color: #999; font-size: 0.9rem;'>" . number_format($car['price'], 0, ',', ' ') . " DH</span>";
            $priceDisplay .= "<div class='card-price' style='color: var(--accent);'>" . number_format($car['sale_price'], 0, ',', ' ') . " DH</div>";
        } else {
            $priceDisplay = "<div class='card-price'>" . number_format($car['price'], 0, ',', ' ') . " DH</div>";
        }

        // Secure output
        $img = htmlspecialchars($car['image']);
        $title = htmlspecialchars($car['make'] . ' ' . $car['model']);
        $year = htmlspecialchars($car['year']);
        $id = $car['id'];

        // --- HTML OUTPUT ---
        echo "
        <div class='car-card'>
            $promoBadge
            <div style='height: 200px; overflow: hidden; position: relative;'>
                <img src='uploads/$img' alt='$title'>
            </div>
            
            <div class='card-body'>
                <h3 class='card-title'>$title</h3>
                <p style='color: #666; font-size: 0.9rem; margin-bottom: 15px;'>
                    Année $year
                </p>
                
                <div style='margin-top: auto;'>
                    $priceDisplay
                </div>
                
                <a href='details.php?id=$id' class='btn btn-blue' style='width:100%; margin-top: 10px; box-sizing: border-box;'>
                    Voir la fiche
                </a>

                <button type='button' class='btn' onclick='toggleCompare($id, this)' style='width:100%; margin-top:5px; background:white; color:#333; border:1px solid #ccc; box-sizing: border-box;'>
                    <i class='fas fa-exchange-alt'></i> Comparer
                </button>
            </div>
        </div>";
    }
    echo "</div>"; // Close car-grid
} else {
    echo "
    <div style='text-align:center; padding:60px; background:rgba(255,255,255,0.9); border-radius:8px; width:100%; grid-column: 1 / -1;'>
        <i class='fas fa-search' style='font-size: 3rem; color: #ccc; margin-bottom: 20px;'></i>
        <h3 style='color: #333;'>Aucun résultat</h3>
        <p style='color: #666;'>Aucune voiture ne correspond à ces critères.</p>
    </div>";
}
?>