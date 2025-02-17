<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Récupérer toutes les commandes de l'utilisateur
$query = "SELECT c.*, b.nom_betail, b.photo, b.prix, u.nom as vendeur_nom 
          FROM commandes c 
          JOIN betail b ON c.betail_id = b.id 
          JOIN users u ON b.vendeur_id = u.id 
          WHERE c.acheteur_id = ? 
          ORDER BY c.date_commande DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$commandes = $stmt->get_result();

// Récupérer les statistiques des commandes
$stats_query = "SELECT 
    COUNT(*) as total_commandes,
    SUM(CASE WHEN statut = 'livre' THEN 1 ELSE 0 END) as commandes_livrees,
    SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as commandes_en_attente,
    SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as commandes_annulees
FROM commandes 
WHERE acheteur_id = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>

<div class="commandes-container">
    <div class="commandes-header">
        <h1>Mes Commandes</h1>
        <a href="<?php echo $_SESSION['role'] === 'vendeur' ? 'dashboard_vendeur.php' : 'dashboard_acheteur.php'; ?>" 
           class="btn btn-secondary">
            <span class="icon">←</span> Retour au tableau de bord
        </a>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
                <h3>Total Commandes</h3>
                <p class="stat-value"><?php echo $stats['total_commandes']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3>Commandes Livrées</h3>
                <p class="stat-value"><?php echo $stats['commandes_livrees']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3>En Attente</h3>
                <p class="stat-value"><?php echo $stats['commandes_en_attente']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <h3>Annulées</h3>
                <p class="stat-value"><?php echo $stats['commandes_annulees']; ?></p>
            </div>
        </div>
    </div>

    <!-- Liste des commandes -->
    <div class="commandes-list">
        <?php if ($commandes->num_rows > 0): ?>
            <?php while ($commande = $commandes->fetch_assoc()): ?>
                <div class="commande-card">
                    <div class="commande-image">
                        <img src="<?php echo htmlspecialchars($commande['photo']); ?>" 
                             alt="<?php echo htmlspecialchars($commande['nom_betail']); ?>">
                        <span class="status-badge status-<?php echo $commande['statut']; ?>">
                            <?php echo ucfirst($commande['statut']); ?>
                        </span>
                    </div>
                    
                    <div class="commande-details">
                        <h3><?php echo htmlspecialchars($commande['nom_betail']); ?></h3>
                        <p class="vendor">Vendeur: <?php echo htmlspecialchars($commande['vendeur_nom']); ?></p>
                        <p class="price"><?php echo number_format($commande['prix'], 0, ',', ' '); ?> FCFA</p>
                        <p class="date">
                            Commandé le: <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?>
                        </p>
                        <?php if ($commande['date_livraison']): ?>
                            <p class="date">
                                Livré le: <?php echo date('d/m/Y H:i', strtotime($commande['date_livraison'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="commande-actions">
                        <a href="detail_commande.php?id=<?php echo $commande['id']; ?>" 
                           class="btn btn-primary">
                            Voir détails
                        </a>
                        <?php if ($commande['statut'] === 'en_attente'): ?>
                            <button onclick="annulerCommande(<?php echo $commande['id']; ?>)" 
                                    class="btn btn-danger">
                                Annuler
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>Vous n'avez pas encore de commandes</p>
                <a href="rechercher.php" class="btn btn-primary">
                    Parcourir les bétails
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function annulerCommande(commandeId) {
    if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
        fetch('api/annuler_commande.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                commande_id: commandeId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erreur lors de l\'annulation');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}
</script>

<style>
.commandes-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.commandes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.commandes-header h1 {
    font-size: 2rem;
    color: var(--text-color);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition-base);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-shadow-hover);
}

.stat-icon {
    font-size: 2rem;
    background: var(--primary-light);
    color: var(--primary-color);
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
}

.stat-content h3 {
    font-size: 0.95rem;
    color: var(--text-light);
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-color);
}

.commandes-list {
    display: grid;
    gap: 1.5rem;
}

.commande-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    display: grid;
    grid-template-columns: 200px 1fr auto;
    gap: 1.5rem;
    padding: 1.5rem;
    transition: var(--transition-base);
}

.commande-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-shadow-hover);
}

.commande-image {
    position: relative;
}

.commande-image img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 12px;
}

.status-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-en_attente {
    background: var(--warning-light);
    color: var(--warning-color);
}

.status-paye {
    background: var(--info-light);
    color: var(--info-color);
}

.status-livre {
    background: var(--success-light);
    color: var(--success-color);
}

.status-annule {
    background: var(--error-light);
    color: var(--error-color);
}

.commande-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.commande-details h3 {
    font-size: 1.25rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.commande-details .vendor {
    color: var(--text-light);
    font-size: 0.95rem;
}

.commande-details .price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
}

.commande-details .date {
    color: var(--text-light);
    font-size: 0.95rem;
}

.commande-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    justify-content: center;
}

.empty-state {
    text-align: center;
    padding: 4rem;
    background: white;
    border-radius: 16px;
    box-shadow: var(--card-shadow);
}

.empty-state p {
    color: var(--text-light);
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
}

@media (max-width: 992px) {
    .commande-card {
        grid-template-columns: 1fr;
    }
    
    .commande-actions {
        flex-direction: row;
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .commandes-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .commande-actions {
        flex-direction: column;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?> 