<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="navbar">
    <div class="nav-brand">MHB Automobiles</div>
    <button class="hamburger" id="hamburger-btn">☰</button>
    <div class="nav-links" id="nav-menu">
        <a href="index.php">Accueil</a>
        <a href="vitrine.php">Vitrine</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="admin.php" style="color:#4CAF50;">Admin Dashboard</a>
            <a href="logout.php" style="color:#ff6b6b;">Déconnexion Admin</a>
        
        <?php elseif (isset($_SESSION['client_id'])): ?>
            <a href="my_account.php" style="color:#3b82f6;">Mon Espace (<?php echo $_SESSION['client_name']; ?>)</a>
            <a href="logout.php" style="color:#ff6b6b;">Déconnexion</a>

        <?php else: ?>
            <a href="login_client.php">Espace Client</a>
            <?php if ($currentPage != 'login.php'): ?>
                <a href="login.php" style="font-size:0.8rem; border:1px solid #666; padding:5px; border-radius:4px; vertical-align: middle;">Accès Staff</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<script>
    document.getElementById('hamburger-btn').addEventListener('click', function() {
        const menu = document.getElementById('nav-menu');
        menu.classList.toggle('active');
    });
</script>