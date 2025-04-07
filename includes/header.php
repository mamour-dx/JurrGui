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
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/js/main.js" defer></script>
    <style>
    /* Styles existants */
    .user-menu {
        position: relative;
    }

    .user-menu-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .user-menu-button:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        padding: 0.5rem;
        min-width: 220px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .user-menu:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: var(--text-color);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background: var(--primary-light);
        color: var(--primary-color);
        transform: translateX(5px);
    }

    .dropdown-item .icon {
        font-size: 1.2rem;
        width: 24px;
        text-align: center;
    }

    .dropdown-divider {
        height: 1px;
        background: var(--border-color);
        margin: 0.5rem 0;
    }

    .dropdown-item:last-child {
        color: var(--error-color);
    }

    .dropdown-item:last-child:hover {
        background: var(--error-light);
        color: var(--error-color);
    }

    @media (max-width: 768px) {
        .user-menu-button {
            padding: 0.5rem;
        }
        
        .dropdown-menu {
            min-width: 200px;
        }
    }
    </style>
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
                            $stmt = $conn->prepare("SELECT COALESCE(SUM(quantite), 0) as total FROM panier WHERE acheteur_id = ?");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $cart_count = $result->fetch_assoc()['total'];
                            if ($cart_count > 0): ?>
                                <span class="badge"><?php echo $cart_count; ?></span>
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
                        <div class="dropdown-menu">
                            <?php if ($_SESSION['role'] === 'acheteur'): ?>
                                <a href="dashboard_acheteur.php" class="dropdown-item">
                                    <span class="icon">🏠</span> Tableau de bord
                                </a>
                                <a href="mes_commandes_acheteur.php" class="dropdown-item">
                                    <span class="icon">📦</span> Mes commandes
                                </a>
                                <a href="profil.php" class="dropdown-item">
                                    <span class="icon">👤</span> Mon profil
                                </a>
                            <?php else: ?>
                                <a href="dashboard_vendeur.php" class="dropdown-item">
                                    <span class="icon">🏠</span> Tableau de bord
                                </a>
                                <a href="mes_commandes_vendeur.php" class="dropdown-item">
                                    <span class="icon">📦</span> Mes commandes
                                </a>
                                <a href="profil.php" class="dropdown-item">
                                    <span class="icon">👤</span> Mon profil
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="deconnexion.php" class="dropdown-item">
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