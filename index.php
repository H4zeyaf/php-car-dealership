<?php 
// 1. Connexion à la base de données
include 'includes/db.php'; 

// --- TRAITEMENT DU FORMULAIRE DE CONTACT ---
if(isset($_POST['send_msg'])) {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $msg = htmlspecialchars($_POST['message']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $msg]);
        $contactSuccess = "Message envoyé avec succès ! Nous vous répondrons sous 24h.";
    } catch (PDOException $e) {
        $contactError = "Erreur lors de l'envoi.";
    }
}

// --- TRAITEMENT DE LA NEWSLETTER (CLUB FIDÉLITÉ) ---
if(isset($_POST['newsletter_email'])) {
    $email = htmlspecialchars($_POST['newsletter_email']);
    try {
        $stmt = $pdo->prepare("INSERT INTO subscribers (email) VALUES (?)");
        $stmt->execute([$email]);
        $newsMsg = "Bienvenue au Club MHB ! 🎉";
        $newsColor = "var(--success)";
    } catch (PDOException $e) {
        $newsMsg = "Vous êtes déjà inscrit !";
        $newsColor = "var(--accent)";
    }
}

// --- TRAITEMENT AJOUT AVIS (TÉMOIGNAGES) ---
if(isset($_POST['submit_review'])) {
    $r_name = htmlspecialchars($_POST['review_name']);
    $r_city = htmlspecialchars($_POST['review_city']);
    $r_msg = htmlspecialchars($_POST['review_msg']);

    if(!empty($r_name) && !empty($r_msg)) {
        $stmt = $pdo->prepare("INSERT INTO reviews (name, city, message) VALUES (?, ?, ?)");
        $stmt->execute([$r_name, $r_city, $r_msg]);
        echo "<script>alert('Merci ! Votre avis a été publié.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MHB Automobiles - L'Excellence Sportive</title>
    
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="overflow-x: hidden;"> <?php include 'includes/navbar.php'; ?>


    <div class="hero">
        <h1 style="font-size: 4rem; margin-bottom: 10px; letter-spacing: -2px;">MHB Automobiles</h1>
        <p style="font-size: 1.3rem; color: #e5e5e5; max-width: 700px; margin: 0 auto; text-shadow: 1px 1px 2px black;">
            L'élégance n'attend pas. Découvrez notre sélection exclusive de véhicules certifiés.
        </p>
        <div style="margin-top: 40px;">
            <a href="vitrine.php" class="cta-button">Accéder au Showroom</a>
        </div>
    </div>


    <?php
    $stmtImages = $pdo->query("SELECT image FROM cars ORDER BY created_at DESC LIMIT 10");
    $images = $stmtImages->fetchAll(PDO::FETCH_COLUMN);
    ?>
    <?php if(count($images) > 0): ?>
    <div class="marquee-container">
        <div class="marquee-content">
            <?php 
            foreach($images as $img) { echo "<img src='uploads/$img' loading='lazy'>"; } 
            foreach($images as $img) { echo "<img src='uploads/$img' loading='lazy'>"; } 
            ?>
        </div>
    </div>
    <?php endif; ?>


    <?php
    // Requête : On cherche les voitures où sale_price > 0
    $stmtPromo = $pdo->query("SELECT * FROM cars WHERE sale_price > 0 LIMIT 3");
    
    if ($stmtPromo->rowCount() > 0):
    ?>
    <div class="section section-light">
        <div class="container">
            <h2 style="color: var(--accent);">🔥 Ventes Flash de la semaine</h2>
            <p style="color: #666; margin-bottom: 40px;">Profitez de ces offres limitées avant qu'il ne soit trop tard.</p>
            
            <div class="car-grid" style="justify-content: center;">
                <?php while ($car = $stmtPromo->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="car-card" style="position: relative;">
                        <div class="badge-promo">
                            -<?php echo round((($car['price'] - $car['sale_price']) / $car['price']) * 100); ?>%
                        </div>
                        
                        <div style="height: 200px; overflow: hidden;">
                            <img src="uploads/<?php echo htmlspecialchars($car['image']); ?>" alt="Car Image">
                        </div>
                        
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></h3>
                            <p style="color:#64748b; font-size:0.9em;"><?php echo $car['year']; ?></p>
                            
                            <div style="margin-top: auto;">
                                <span class="old-price"><?php echo number_format($car['price'], 0, ',', ' '); ?> DH</span>
                                <span class="new-price"><?php echo number_format($car['sale_price'], 0, ',', ' '); ?> DH</span>
                            </div>
                            
                            <a href="details.php?id=<?php echo $car['id']; ?>" class="btn btn-blue" style="width:100%; margin-top:10px; text-align:center;">
                                Voir l'offre
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <div class="section section-dark">
        <h2>Nos Services Premium</h2>
        <div class="services-grid">
            <div class="service-card">
                <i class="fas fa-key service-icon"></i>
                <h3>Clés en main</h3>
                <p style="color:#9ca3af;">On s'occupe de la carte grise, de l'assurance et de la livraison à domicile.</p>
            </div>
            <div class="service-card">
                <i class="fas fa-shield-alt service-icon"></i>
                <h3>Garantie Or</h3>
                <p style="color:#9ca3af;">Tous nos véhicules sont garantis 12 ou 24 mois, pièces et main d'œuvre incluses.</p>
            </div>
            <div class="service-card">
                <i class="fas fa-hand-holding-usd service-icon"></i>
                <h3>Financement</h3>
                <p style="color:#9ca3af;">Simulez votre crédit directement avec nos partenaires (Eqdom, Wafasalaf).</p>
            </div>
        </div>
    </div>


    <div class="section section-light">
        <div class="loyalty-card">
            <div style="text-align: left;">
                <h2 style="margin: 0; font-size: 2rem;">Rejoignez le Club MHB</h2>
                <p style="font-size: 1.1rem; opacity: 0.9;">Accédez aux ventes privées 24h avant tout le monde et recevez nos conseils.</p>
            </div>
            <div style="background: white; padding: 25px; border-radius: 8px; min-width: 320px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                
                <?php if(isset($newsMsg)) echo "<p style='color:$newsColor; font-weight:bold; margin-top:0;'>$newsMsg</p>"; ?>
                
                <form method="POST">
                    <input type="email" name="newsletter_email" placeholder="Votre email pro..." style="margin-bottom: 10px; border:1px solid #ddd;" required>
                    <button type="submit" class="btn" style="width: 100%; background: var(--accent); font-weight:bold;">M'inscrire gratuitement</button>
                </form>
                <p style="color:#666; font-size:0.8rem; margin-top:10px; text-align:center;">Pas de spam, promis.</p>
            </div>
        </div>
    </div>


    <div class="section section-dark">
        <h2>Ils nous font confiance</h2>
        
        <div class="container">
            <div class="testimonials-grid">
                <?php
                // Récupération des avis depuis la BDD
                $stmtReviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC");
                while ($review = $stmtReviews->fetch(PDO::FETCH_ASSOC)): 
                ?>
                <div class="testimonial-card">
                    <i class="fas fa-quote-right quote-icon" style="opacity: 0.1;"></i>
                    
                    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px; min-height: 80px; color: #333; position: relative; z-index: 2;">
                        <?php echo nl2br(htmlspecialchars($review['message'])); ?>
                    </div>

                    <div class="user-info" style="display: block; margin-top: 0;">
                        <strong style="color: #111; font-size: 1.1rem;"><?php echo htmlspecialchars($review['name']); ?></strong><br>
                        <small style="color: #666; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;">
                            <?php echo htmlspecialchars($review['city']); ?>
                        </small>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="review-form-container">
            <h3 style="color: var(--primary); margin-top:0; text-align:center;">Laissez votre avis</h3>
            <form method="POST">
                <div style="display: flex; gap: 20px;">
                    <input type="text" name="review_name" placeholder="Votre Nom" required style="background:#f9fafb;">
                    <input type="text" name="review_city" placeholder="Ville" style="background:#f9fafb;">
                </div>
                <textarea name="review_msg" placeholder="Racontez votre expérience..." rows="3" required style="background:#f9fafb;"></textarea>
                <button type="submit" name="submit_review" class="btn btn-blue" style="width: 100%;">Publier mon avis</button>
            </form>
        </div>

    </div>


    <div class="section section-dark" style="background: rgba(0,0,0,0.8); padding-top: 40px;">
        <h2 style="font-size: 2rem;">Questions Fréquentes</h2>
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question">Peut-on payer en plusieurs fois ?</div>
                <div class="faq-answer">Oui, nous avons des partenariats pour financer votre achat jusqu'à 60 mois.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">Les voitures sont-elles expertisées ?</div>
                <div class="faq-answer">Absolument. Chaque véhicule passe par un contrôle technique de 120 points.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">Faites-vous la livraison ?</div>
                <div class="faq-answer">Nous livrons partout au Maroc sous 48h après validation du dossier.</div>
            </div>
        </div>
    </div>


    <div class="section section-light" id="contact">
        <h2>Contactez-nous</h2>
        <p style="margin-bottom: 30px; color: #666;">Une question ? Un essai ? Écrivez-nous.</p>
        
        <div class="contact-form">
            <?php if(isset($contactSuccess)) echo "<div style='background: var(--success); color:white; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>$contactSuccess</div>"; ?>
            <?php if(isset($contactError)) echo "<div style='background: var(--danger); color:white; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>$contactError</div>"; ?>

            <form method="POST">
                <label style="color:#333;">Votre Nom</label>
                <input type="text" name="name" class="contact-input" style="background:white; color:#333;" required>
                
                <label style="color:#333;">Votre Email</label>
                <input type="email" name="email" class="contact-input" style="background:white; color:#333;" required>
                
                <label style="color:#333;">Message</label>
                <textarea name="message" class="contact-input" rows="5" style="background:white; color:#333;" required></textarea>
                
                <button type="submit" name="send_msg" class="btn btn-blue" style="width: 100%;">Envoyer le message</button>
            </form>
        </div>
    </div>


    <?php if (file_exists('includes/footer.php')) include 'includes/footer.php'; ?>

</body>
</html>