<?php
require_once 'includes/header.php';

if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$commande_id = clean($conn, $_GET['id']);

$conn = connectDB();
$stmt = $conn->prepare("
    SELECT c.*, b.nom_betail, b.photo, u.nom as vendeur_nom
    FROM commandes c
    JOIN betail b ON c.betail_id = b.id
    JOIN users u ON b.vendeur_id = u.id
    WHERE c.commande_id = ? AND c.acheteur_id = ?
");
$stmt->bind_param("si", $commande_id, $_SESSION['user_id']);
$stmt->execute();
$commandes = $stmt->get_result();

if ($commandes->num_rows === 0) {
    header('Location: index.php');
    exit();
}
?>

<div class="confirmation-container">
    <div class="confirmation-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1>Commande confirmée !</h1>
        <p class="reference">Référence : <?php echo htmlspecialchars($commande_id); ?></p>
        
        <div class="confirmation-details">
            <h3>Récapitulatif de votre commande</h3>
            
            <?php while ($commande = $commandes->fetch_assoc()): ?>
                <div class="article-resume">
                    <img src="<?php echo htmlspecialchars($commande['photo']); ?>" 
                         alt="<?php echo htmlspecialchars($commande['nom_betail']); ?>">
                    <div class="article-info">
                        <h4><?php echo htmlspecialchars($commande['nom_betail']); ?></h4>
                        <p>Vendeur: <?php echo htmlspecialchars($commande['vendeur_nom']); ?></p>
                        <p>Quantité: <?php echo $commande['quantite']; ?></p>
                        <p class="prix">
                            <?php echo number_format($commande['montant'], 0, ',', ' '); ?> FCFA
                        </p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="confirmation-message">
            <p>Un email de confirmation a été envoyé à votre adresse.</p>
            <p>Les vendeurs ont été notifiés et vous contacteront bientôt.</p>
        </div>
        
        <div class="confirmation-actions">
            <a href="dashboard_acheteur.php" class="btn btn-primary">Voir mes commandes</a>
            <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
        </div>
    </div>
</div>

<style>
.confirmation-container {
    max-width: 800px;
    margin: 3rem auto;
    padding: 0 1rem;
}

.confirmation-card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.success-icon {
    color: #10b981;
    font-size: 4rem;
    margin-bottom: 1rem;
}

.reference {
    color: #6b7280;
    margin: 1rem 0;
}

.confirmation-details {
    margin: 2rem 0;
    text-align: left;
}

.article-resume {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin: 1rem 0;
}

.article-resume img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
}

.confirmation-message {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin: 2rem 0;
}

.confirmation-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

@media (max-width: 640px) {
    .confirmation-actions {
        flex-direction: column;
    }
    
    .article-resume {
        flex-direction: column;
    }
    
    .article-resume img {
        width: 100%;
        height: 200px;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
