<?php
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$conn = connectDB();
$betail_id = intval($_GET['id']);

// Récupération des détails du bétail
$stmt = $conn->prepare("
    SELECT b.*, u.nom as vendeur_nom, u.email as vendeur_email, u.telephone as vendeur_telephone,
           (SELECT AVG(note) FROM avis WHERE vendeur_id = u.id) as note_vendeur,
           (SELECT COUNT(*) FROM avis WHERE vendeur_id = u.id) as nombre_avis
    FROM betail b
    JOIN users u ON b.vendeur_id = u.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $betail_id);
$stmt->execute();
$betail = $stmt->get_result()->fetch_assoc();

if (!$betail) {
    header('Location: index.php');
    exit();
}

// Récupération des avis sur le vendeur
$stmt = $conn->prepare("
    SELECT a.*, u.nom as acheteur_nom
    FROM avis a
    JOIN users u ON a.acheteur_id = u.id
    WHERE a.vendeur_id = ?
    ORDER BY a.date_avis DESC
    LIMIT 5
");
$stmt->bind_param("i", $betail['vendeur_id']);
$stmt->execute();
$avis = $stmt->get_result();

// Récupération des annonces similaires
$stmt = $conn->prepare("
    SELECT b.*, u.nom as vendeur_nom
    FROM betail b
    JOIN users u ON b.vendeur_id = u.id
    WHERE b.categorie = ? AND b.id != ?
    ORDER BY b.date_publication DESC
    LIMIT 4
");
$stmt->bind_param("si", $betail['categorie'], $betail_id);
$stmt->execute();
$similaires = $stmt->get_result();
?>

<div class="detail-container">
    <div class="detail-main">
        <div class="detail-gallery">
            <img src="<?php echo htmlspecialchars($betail['photo']); ?>" 
                 alt="<?php echo htmlspecialchars($betail['nom_betail']); ?>"
                 class="main-image">
        </div>
        
        <div class="detail-info">
            <h1><?php echo htmlspecialchars($betail['nom_betail']); ?></h1>
            
            <div class="price-category">
                <p class="price"><?php echo number_format($betail['prix'], 0, ',', ' '); ?> FCFA</p>
                <p class="category"><?php echo ucfirst($betail['categorie']); ?></p>
            </div>
            
            <div class="vendor-info">
                <h3>Vendeur: <?php echo htmlspecialchars($betail['vendeur_nom']); ?></h3>
                <?php if ($betail['note_vendeur']): ?>
                    <p class="rating">
                        ⭐ <?php echo number_format($betail['note_vendeur'], 1); ?> 
                        (<?php echo $betail['nombre_avis']; ?> avis)
                    </p>
                <?php endif; ?>
                
                <div class="contact-info">
                    <h4>Informations de contact</h4>
                    <p class="phone">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?php echo htmlspecialchars($betail['vendeur_telephone']); ?>">
                            <?php echo htmlspecialchars($betail['vendeur_telephone']); ?>
                        </a>
                    </p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <p class="email">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?php echo htmlspecialchars($betail['vendeur_email']); ?>">
                                <?php echo htmlspecialchars($betail['vendeur_email']); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="description">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($betail['description'])); ?></p>
            </div>
            
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'acheteur'): ?>
                <div class="action-buttons">
                    <button onclick="ajouterAuPanier(<?php echo $betail['id']; ?>)" 
                            class="btn btn-primary">
                        Ajouter au panier
                    </button>
                    <button onclick="acheterMaintenant(<?php echo $betail['id']; ?>)" 
                            class="btn btn-secondary">
                        Acheter maintenant
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="detail-sections">
        <section class="vendor-reviews">
            <h2>Avis sur le vendeur</h2>
            <?php if ($avis->num_rows > 0): ?>
                <div class="reviews-list">
                    <?php while ($review = $avis->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <p class="reviewer"><?php echo htmlspecialchars($review['acheteur_nom']); ?></p>
                                <p class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $review['note'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </p>
                            </div>
                            <p class="review-comment"><?php echo htmlspecialchars($review['commentaire']); ?></p>
                            <p class="review-date">
                                <?php echo date('d/m/Y', strtotime($review['date_avis'])); ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Aucun avis pour le moment</p>
            <?php endif; ?>
        </section>
        
        <section class="similar-items">
            <h2>Annonces similaires</h2>
            <div class="similar-grid">
                <?php while ($sim = $similaires->fetch_assoc()): ?>
                    <div class="similar-card">
                        <img src="<?php echo htmlspecialchars($sim['photo']); ?>" 
                             alt="<?php echo htmlspecialchars($sim['nom_betail']); ?>">
                        <div class="similar-info">
                            <h3><?php echo htmlspecialchars($sim['nom_betail']); ?></h3>
                            <p class="price"><?php echo number_format($sim['prix'], 0, ',', ' '); ?> FCFA</p>
                            <a href="detail_betail.php?id=<?php echo $sim['id']; ?>" 
                               class="btn btn-secondary">Voir détails</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>
</div>

<script>
function ajouterAuPanier(betailId) {
    fetch('api/ajouter_panier.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ betail_id: betailId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produit ajouté au panier !');
            updateCartCount(data.cart_count);
        } else {
            alert(data.message || 'Erreur lors de l\'ajout au panier');
        }
    });
}

function acheterMaintenant(betailId) {
    // Ajouter au panier puis rediriger vers la page de paiement
    fetch('api/ajouter_panier.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            betail_id: betailId,
            achat_direct: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'paiement.php';
        } else {
            alert(data.message || 'Erreur lors de l\'achat');
        }
    });
}
</script>

<style>
.detail-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.detail-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.detail-gallery {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
}

.main-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

.detail-info {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.price-category {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary-color);
}

.vendor-info {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.vendor-info h3 {
    color: #333;
    margin-bottom: 1rem;
}

.contact-info {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #dee2e6;
}

.contact-info h4 {
    color: #555;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.contact-info p {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.contact-info i {
    margin-right: 0.75rem;
    color: #007bff;
    width: 20px;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 123, 255, 0.1);
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.contact-info a:hover i {
    transform: scale(1.1);
    background: rgba(0, 123, 255, 0.2);
}

.contact-info .phone i {
    transform: rotate(15deg);
}

.contact-info .email i {
    font-size: 1.1rem;
}

.contact-info a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-info a:hover {
    color: #007bff;
}

.phone {
    font-size: 1.1rem;
    font-weight: 500;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: auto;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}

.review-card {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.similar-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.similar-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.similar-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.similar-info {
    padding: 1rem;
}

@media (max-width: 768px) {
    .detail-main {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
