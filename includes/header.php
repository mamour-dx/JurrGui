<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marché de Bétail en Ligne</title>
    <link rel="stylesheet" href="/JurrGui/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="/JurrGui/assets/js/main.js" defer></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="index.php">
                    <img src="logo-1.png" alt="Logo JurrGui" width="32" height="32">
                    JurrGui
                </a>
            </div>
            <div class="menu">
                <a href="index.php" class="<?php echo ($_SERVER['PHP_SELF'] == '/index.php') ? 'active' : ''; ?>">
                    <span class="icon">🏠</span> Accueil
                </a>
                <a href="rechercher.php" class="<?php echo ($_SERVER['PHP_SELF'] == '/rechercher.php') ? 'active' : ''; ?>">
                    <span class="icon">🔍</span> Rechercher
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'vendeur'): ?>
                        <a href="dashboard_vendeur.php" class="<?php echo ($_SERVER['PHP_SELF'] == '/dashboard_vendeur.php') ? 'active' : ''; ?>">
                            <span class="icon">📊</span> Dashboard
                        </a>
                    <?php elseif ($_SESSION['role'] === 'acheteur'): ?>
                        <a href="dashboard_acheteur.php" class="<?php echo ($_SERVER['PHP_SELF'] == '/dashboard_acheteur.php') ? 'active' : ''; ?>">
                            <span class="icon">📊</span> Dashboard
                        </a>
                        <a href="panier.php" class="<?php echo ($_SERVER['PHP_SELF'] == '/panier.php') ? 'active' : ''; ?>">
                            <span class="icon">🛒</span> Panier
                            <?php
                            $conn = connectDB();
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM panier WHERE acheteur_id = ?");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $count = $result->fetch_assoc()['count'];
                            if ($count > 0): ?>
                                <span class="badge"><?php echo $count; ?></span>
                            <?php endif; 
                            $conn->close();
                            ?>
                        </a>
                    <?php endif; ?>
                    
                    <div class="user-menu">
                        <button class="user-menu-button">
                            <span class="icon">👤</span>
                            <?php echo htmlspecialchars($_SESSION['nom']); ?>
                            <span class="icon">▼</span>
                        </button>
                        <div class="user-menu-dropdown">
                            <a href="profil.php">
                                <span class="icon">👤</span> Profil
                            </a>
                            <a href="mes_commandes.php">
                                <span class="icon">📦</span> Mes commandes
                            </a>
                            <a href="deconnexion.php" class="text-error">
                                <span class="icon">🚪</span> Déconnexion
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="connexion.php" class="btn btn-secondary">
                        <span class="icon">🔑</span> Connexion
                    </a>
                    <a href="inscription.php" class="btn btn-primary">
                        <span class="icon">✨</span> Inscription
                    </a>
                <?php endif; ?>
            </div>
            <button class="hamburger" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>
    <main></main>