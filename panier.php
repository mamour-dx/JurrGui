<?php
require_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    header('Location: connexion.php');
    exit();
}

$conn = connectDB();

// Récupération des articles du panier depuis la base de données
$articles = [];
$total = 0;

$stmt = $conn->prepare("
    SELECT b.*, u.nom as vendeur_nom, p.quantite as quantite_panier
    FROM panier p
    JOIN betail b ON p.betail_id = b.id
    JOIN users u ON b.vendeur_id = u.id
    WHERE p.acheteur_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($article = $result->fetch_assoc()) {
    $article['quantite'] = $article['quantite_panier'];
    $article['sous_total'] = $article['prix'] * $article['quantite'];
    $total += $article['sous_total'];
    $articles[] = $article;
}
?>

<div class="panier-container">
    <h1>Mon Panier</h1>
    
    <?php if (empty($articles)): ?>
        <div class="panier-vide">
            <img src="assets/images/empty-cart.svg" alt="Panier vide">
            <h2>Votre panier est vide</h2>
            <p>Parcourez nos annonces pour trouver du bétail de qualité</p>
            <a href="rechercher.php" class="btn btn-primary">Voir les annonces</a>
        </div>
    <?php else: ?>
        <div class="panier-grid">
            <div class="articles-list">
                <?php foreach ($articles as $article): ?>
                    <div class="article-card" data-id="<?php echo $article['id']; ?>">
                        <div class="article-image">
                            <img src="<?php echo htmlspecialchars($article['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($article['nom_betail']); ?>">
                        </div>
                        
                        <div class="article-info">
                            <h3><?php echo htmlspecialchars($article['nom_betail']); ?></h3>
                            <p class="vendeur">Vendeur: <?php echo htmlspecialchars($article['vendeur_nom']); ?></p>
                            <p class="prix"><?php echo number_format($article['prix'], 0, ',', ' '); ?> FCFA</p>
                        </div>
                        
                        <div class="article-actions">
                            <div class="quantite-controls">
                                <button onclick="updateQuantite(<?php echo $article['id']; ?>, 'decrease')" 
                                        class="btn-quantite">-</button>
                                <span class="quantite"><?php echo $article['quantite']; ?></span>
                                <button onclick="updateQuantite(<?php echo $article['id']; ?>, 'increase')" 
                                        class="btn-quantite">+</button>
                            </div>
                            <p class="sous-total">
                                Sous-total: <?php echo number_format($article['sous_total'], 0, ',', ' '); ?> FCFA
                            </p>
                            <button onclick="removeArticle(<?php echo $article['id']; ?>)" 
                                    class="btn btn-danger">Supprimer</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="panier-resume">
                <div class="resume-card">
                    <h3>Résumé de la commande</h3>
                    <div class="resume-details">
                        <div class="resume-line">
                            <span>Sous-total</span>
                            <span><?php echo number_format($total, 0, ',', ' '); ?> FCFA</span>
                        </div>
                        <div class="resume-line">
                            <span>Frais de service (2%)</span>
                            <span><?php echo number_format($total * 0.02, 0, ',', ' '); ?> FCFA</span>
                        </div>
                        <div class="resume-total">
                            <span>Total</span>
                            <span><?php echo number_format($total * 1.02, 0, ',', ' '); ?> FCFA</span>
                        </div>
                    </div>
                    <a href="paiement.php" class="btn btn-primary btn-block">Procéder au paiement</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQuantite(betailId, action) {
    fetch('api/update_panier.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            betail_id: betailId,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erreur lors de la mise à jour du panier');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la mise à jour');
    });
}

function removeArticle(betailId) {
    if (confirm('Êtes-vous sûr de vouloir retirer cet article du panier ?')) {
        fetch('api/retirer_panier.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                betail_id: betailId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la suppression');
        });
    }
}
</script>

<style>
.panier-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.panier-vide {
    text-align: center;
    padding: 3rem;
}

.panier-vide img {
    max-width: 200px;
    margin-bottom: 1rem;
}

.panier-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
}

.article-card {
    display: grid;
    grid-template-columns: 150px 1fr auto;
    gap: 1rem;
    background: white;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.article-image img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
}

.article-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.article-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-end;
}

.quantite-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-quantite {
    width: 30px;
    height: 30px;
    border: 1px solid var(--border-color);
    background: white;
    border-radius: 4px;
    cursor: pointer;
}

.resume-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    position: sticky;
    top: 2rem;
}

.resume-details {
    margin: 1.5rem 0;
}

.resume-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.resume-total {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    font-weight: bold;
}

@media (max-width: 768px) {
    .panier-grid {
        grid-template-columns: 1fr;
    }
    
    .article-card {
        grid-template-columns: 1fr;
    }
    
    .article-image {
        text-align: center;
    }
    
    .article-image img {
        width: 200px;
        height: 150px;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
