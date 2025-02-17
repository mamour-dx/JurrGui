<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/header.php';
$conn = connectDB();

// Récupération des dernières annonces
$query = "SELECT b.*, u.nom as vendeur_nom, 
          (SELECT AVG(note) FROM avis WHERE vendeur_id = u.id) as note_moyenne
          FROM betail b 
          JOIN users u ON b.vendeur_id = u.id 
          ORDER BY b.date_publication DESC 
          LIMIT 6";
$result = $conn->query($query);
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content animate-fade-in" style="--delay: 0.2">
            <h1>Trouvez le meilleur bétail pour votre élevage</h1>
            <p>Plateforme de vente de bétail en ligne sécurisée et fiable</p>
            <div class="hero-buttons">
                <a href="listings.php" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Parcourir les annonces
                </a>
                <a href="inscription.php" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i>
                    Créer un compte
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories">
    <div class="container">
        <h2 class="section-title text-center">Nos Catégories</h2>
        <div class="category-grid">
            <!-- Bovins -->
            <div class="category-card animate-fade-in" style="--delay: 0.3">
                <div class="category-image">
                    <img src="assets/images/bovins.jpeg" alt="Bovins" loading="lazy">
                </div>
                <div class="category-overlay">
                    <div class="category-content">
                        <i class="category-icon">
                            <?php include 'assets/images/categories/bovins-icon.svg'; ?>
                        </i>
                        <h3>Bovins</h3>
                        <p>Découvrez notre sélection de bovins de qualité</p>
                    </div>
                </div>
                <a href="listings.php?category=bovins" class="stretched-link"></a>
            </div>

            <!-- Ovins -->
            <div class="category-card animate-fade-in" style="--delay: 0.4">
                <div class="category-image">
                    <img src="assets/images/ovins.jpeg" alt="Ovins" loading="lazy">
                </div>
                <div class="category-overlay">
                    <div class="category-content">
                        <i class="category-icon">
                            <?php include 'assets/images/categories/ovins-icon.svg'; ?>
                        </i>
                        <h3>Ovins</h3>
                        <p>Explorez notre gamme d'ovins sélectionnés</p>
                    </div>
                </div>
                <a href="listings.php?category=ovins" class="stretched-link"></a>
            </div>

            <!-- Caprins -->
            <div class="category-card animate-fade-in" style="--delay: 0.5">
                <div class="category-image">
                    <img src="assets/images/caprins.jpeg" alt="Caprins" loading="lazy">
                </div>
                <div class="category-overlay">
                    <div class="category-content">
                        <i class="category-icon">
                            <?php include 'assets/images/categories/caprins-icon.svg'; ?>
                        </i>
                        <h3>Caprins</h3>
                        <p>Trouvez les meilleurs caprins pour votre élevage</p>
                    </div>
                </div>
                <a href="listings.php?category=caprins" class="stretched-link"></a>
            </div>
        </div>
    </div>
</section>

<!-- Latest Listings Section -->
<section class="latest-listings">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Dernières Annonces</h2>
            <a href="listings.php" class="btn btn-outline">
                <i class="fas fa-list"></i>
                Voir toutes les annonces
            </a>
        </div>
        
        <div class="listings-grid">
            <?php
            if ($result && $result->num_rows > 0):
                while ($betail = $result->fetch_assoc()):
                    ?>
                    <div class="listing-card animate-fade-in">
                        <div class="listing-image">
                            <img src="<?php echo htmlspecialchars($betail['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($betail['nom_betail']); ?>"
                                 loading="lazy">
                            <span class="listing-category">
                                <?php 
                                $icon_path = 'assets/images/categories/' . $betail['categorie'] . '-icon.svg';
                                if (file_exists($icon_path)) {
                                    include $icon_path;
                                }
                                echo ucfirst($betail['categorie']); 
                                ?>
                            </span>
                        </div>
                        <div class="listing-content">
                            <h3><?php echo htmlspecialchars($betail['nom_betail']); ?></h3>
                            <p class="listing-price"><?php echo number_format($betail['prix'], 0, ',', ' '); ?> FCFA</p>
                            <div class="listing-meta">
                                <span class="listing-seller">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($betail['vendeur_nom']); ?>
                                </span>
                                <a href="listing.php?id=<?php echo $betail['id']; ?>" class="btn btn-sm btn-primary">
                                    Voir détails
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                endwhile;
            else:
                ?>
                <div class="no-listings">
                    <i class="fas fa-inbox fa-3x"></i>
                    <p>Aucune annonce disponible pour le moment</p>
                    <a href="create-listing.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Publier une annonce
                    </a>
                </div>
                <?php
            endif;
            ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nav = document.querySelector('nav');
    const menu = document.querySelector('.menu');
    
    // Créer le bouton hamburger
    const hamburger = document.createElement('button');
    hamburger.classList.add('hamburger');
    hamburger.innerHTML = `
        <span></span>
        <span></span>
        <span></span>
    `;
    
    // Ajouter le bouton au nav sur mobile
    if (window.innerWidth <= 768) {
        nav.appendChild(hamburger);
    }
    
    // Gérer le clic sur le hamburger
    hamburger.addEventListener('click', function() {
        menu.classList.toggle('active');
        hamburger.classList.toggle('active');
    });
    
    // Gérer le redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            if (!nav.contains(hamburger)) {
                nav.appendChild(hamburger);
            }
        } else {
            if (nav.contains(hamburger)) {
                nav.removeChild(hamburger);
            }
            menu.classList.remove('active');
        }
    });
});
</script>
<?php
$conn->close();
require_once 'includes/footer.php';
?>
