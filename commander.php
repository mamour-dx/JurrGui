<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté et est un acheteur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    header('Location: connexion.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = connectDB();

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Récupérer les articles du panier
$stmt = $conn->prepare("
    SELECT p.*, b.nom_betail, b.prix, b.photo, b.vendeur_id, u.nom as vendeur_nom
    FROM panier p
    JOIN betail b ON p.betail_id = b.id
    JOIN users u ON b.vendeur_id = u.id
    WHERE p.acheteur_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$articles = $stmt->get_result();

// Calculer le total
$total = 0;
while ($article = $articles->fetch_assoc()) {
    $total += $article['prix'] * $article['quantite'];
}
$articles->data_seek(0); // Réinitialiser le pointeur pour la réutilisation

// Gestion de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();
        
        // Pour chaque article dans le panier
        $articles->data_seek(0);
        while ($article = $articles->fetch_assoc()) {
            // Création de la commande pour chaque article
            $stmt = $conn->prepare("
                INSERT INTO commandes (
                    acheteur_id,
                    betail_id,
                    methode_paiement,
                    statut,
                    date_commande
                ) VALUES (?, ?, ?, 'en_attente', NOW())
            ");
            
            $stmt->bind_param(
                "iis",
                $_SESSION['user_id'],
                $article['betail_id'],
                $_POST['methode_paiement']
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Erreur lors de la création de la commande: ' . $stmt->error);
            }
            
            $commande_id = $conn->insert_id;
            
            // Mettre à jour le statut du bétail
            $stmt = $conn->prepare("
                UPDATE betail 
                SET statut = 'reserve' 
                WHERE id = ?
            ");
            
            $stmt->bind_param("i", $article['betail_id']);
            
            if (!$stmt->execute()) {
                throw new Exception('Erreur lors de la mise à jour du statut: ' . $stmt->error);
            }
        }
        
        // Supprimer les articles du panier
        $stmt = $conn->prepare("
            DELETE FROM panier 
            WHERE acheteur_id = ?
        ");
        
        $stmt->bind_param("i", $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Erreur lors de la suppression du panier: ' . $stmt->error);
        }
        
        $conn->commit();
        
        // Rediriger vers la page de confirmation de paiement
        header("Location: confirmation_paiement.php?id=" . $commande_id);
        exit();
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        $error = $e->getMessage();
    }
}
?>

<div class="commander-container">
    <h1>Passer une commande</h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="commander-grid">
        <!-- Récapitulatif du panier -->
        <div class="recap-section">
            <h2>Récapitulatif de votre commande</h2>
            <div class="articles-list">
                <?php while ($article = $articles->fetch_assoc()): ?>
                    <div class="article-card" data-id="<?php echo $article['betail_id']; ?>">
                        <div class="article-image">
                            <img src="<?php echo htmlspecialchars($article['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($article['nom_betail']); ?>">
                        </div>
                        <div class="article-info">
                            <h3><?php echo htmlspecialchars($article['nom_betail']); ?></h3>
                            <p class="vendeur">Vendeur: <?php echo htmlspecialchars($article['vendeur_nom']); ?></p>
                            <p class="quantite">Quantité: <?php echo $article['quantite']; ?></p>
                            <p class="prix">Prix unitaire: <?php echo number_format($article['prix'], 0, ',', ' '); ?> FCFA</p>
                            <p class="sous-total">Sous-total: <?php echo number_format($article['prix'] * $article['quantite'], 0, ',', ' '); ?> FCFA</p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="total-section">
                <h3>Total de la commande</h3>
                <p class="total"><?php echo number_format($total, 0, ',', ' '); ?> FCFA</p>
            </div>
        </div>
        
        <!-- Formulaire de commande -->
        <div class="form-section">
            <form method="POST" action="">
                <div class="form-group">
                    <h2>Informations de livraison</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <label for="nom">Nom complet</label>
                            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                        </div>
                        <div class="form-col">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <label for="adresse">Adresse de livraison</label>
                        <textarea id="adresse" name="adresse" required><?php echo htmlspecialchars($user['adresse'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <h2>Méthode de paiement</h2>
                    <div class="paiement-options">
                        <label class="paiement-option">
                            <input type="radio" name="methode_paiement" value="wave" required>
                            <span>Wave</span>
                        </label>
                        
                        <label class="paiement-option">
                            <input type="radio" name="methode_paiement" value="orange_money">
                            <span>Orange Money</span>
                        </label>
                        
                        <label class="paiement-option">
                            <input type="radio" name="methode_paiement" value="livraison">
                            <span>Paiement à la livraison</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-large">
                        Valider la commande
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.commander-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.commander-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.recap-section, .form-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: var(--card-shadow);
}

.articles-list {
    margin-bottom: 2rem;
}

.article-card {
    display: grid;
    grid-template-columns: 100px 1fr;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.article-image img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}

.article-info h3 {
    margin-bottom: 0.5rem;
}

.vendeur, .quantite, .prix {
    color: var(--text-light);
    margin-bottom: 0.25rem;
}

.sous-total {
    font-weight: bold;
    color: var(--primary-color);
}

.total-section {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 2px solid var(--border-color);
}

.total-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.grand-total {
    font-size: 1.2rem;
    font-weight: bold;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.form-group {
    margin-bottom: 2rem;
}

.form-group h2 {
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.form-row {
    margin-bottom: 1rem;
}

.form-col {
    margin-bottom: 1rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

input, textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

.paiement-options {
    display: flex;
    gap: 20px;
    margin-top: 15px;
}

.paiement-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    cursor: pointer;
}

.paiement-option input[type="radio"] {
    margin: 0;
}

.paiement-option:hover {
    border-color: #007bff;
}

.paiement-icon {
    font-size: 16px;
    font-weight: 500;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .commander-grid {
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
    
    .form-actions {
        flex-direction: column;
    }
}

.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #fff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    background: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateX(120%);
    transition: transform 0.3s ease;
    z-index: 1000;
}

.notification.show {
    transform: translateX(0);
}

.notification.success {
    border-left: 4px solid #28a745;
}

.notification.error {
    border-left: 4px solid #dc3545;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.notification-content i {
    font-size: 20px;
}

.notification.success .notification-content i {
    color: #28a745;
}

.notification.error .notification-content i {
    color: #dc3545;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('commandeForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!form || !submitBtn) {
        console.error('Form or submit button not found');
        return;
    }
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours...';
        
        try {
            // Récupérer les articles du panier
            const articles = [];
            const articleCards = document.querySelectorAll('.article-card');
            
            articleCards.forEach(card => {
                const betailId = card.getAttribute('data-id');
                const quantite = parseInt(card.querySelector('.quantite').textContent);
                const prix = parseFloat(card.querySelector('.prix').textContent);
                
                articles.push({
                    betail_id: betailId,
                    quantite: quantite,
                    prix_unitaire: prix
                });
            });
            
            // Récupérer les informations du formulaire
            const formData = {
                action: 'creer_commande',
                articles: articles,
                nom_livraison: document.getElementById('nom_livraison').value,
                telephone_livraison: document.getElementById('telephone_livraison').value,
                adresse_livraison: document.getElementById('adresse_livraison').value,
                methode_paiement: document.querySelector('input[name="methode_paiement"]:checked').value
            };
            
            console.log('Données envoyées:', formData);
            
            const response = await fetch('api/creer_commande.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            console.log('Réponse reçue:', result);
            
            if (result.success) {
                showNotification('success', 'Commande créée avec succès');
                // Rediriger vers la page de confirmation de paiement
                setTimeout(() => {
                    window.location.href = `confirmation_paiement.php?id=${result.commande_id}`;
                }, 1500);
            } else {
                showNotification('error', result.message || 'Erreur lors de la création de la commande');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Valider la commande';
            }
        } catch (error) {
            console.error('Erreur:', error);
            showNotification('error', 'Une erreur est survenue lors de la création de la commande');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Valider la commande';
        }
    });
    
    function showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
});
</script>

<?php
$conn->close();
require_once 'includes/footer.php';
?> 