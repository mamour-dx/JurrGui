<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté et est un acheteur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    header('Location: connexion.php');
    exit();
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Récupérer les statistiques de l'acheteur
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM commandes WHERE acheteur_id = ?) as total_commandes,
    (SELECT COUNT(*) FROM commandes WHERE acheteur_id = ? AND statut = 'livre') as commandes_livrees,
    (SELECT COUNT(*) FROM commandes WHERE acheteur_id = ? AND statut = 'en_attente') as commandes_en_attente,
    (SELECT COUNT(*) FROM panier WHERE acheteur_id = ?) as articles_panier";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Récupérer les dernières commandes
$commandes_query = "SELECT c.*, b.nom_betail, b.photo, b.prix, u.nom as vendeur_nom 
                   FROM commandes c 
                   JOIN betail b ON c.betail_id = b.id 
                   JOIN users u ON b.vendeur_id = u.id 
                   WHERE c.acheteur_id = ? 
                   ORDER BY c.date_commande DESC 
                   LIMIT 5";

$stmt = $conn->prepare($commandes_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$commandes = $stmt->get_result();

// Récupérer les articles du panier
$panier_query = "SELECT p.*, b.nom_betail, b.photo, b.prix, u.nom as vendeur_nom 
                 FROM panier p 
                 JOIN betail b ON p.betail_id = b.id 
                 JOIN users u ON b.vendeur_id = u.id 
                 WHERE p.acheteur_id = ?";

$stmt = $conn->prepare($panier_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$panier = $stmt->get_result();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Tableau de bord Acheteur</h1>
        <a href="rechercher.php" class="btn btn-primary">
            <span class="icon">🔍</span> Parcourir les bétails
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
                <h3>Commandes en Attente</h3>
                <p class="stat-value"><?php echo $stats['commandes_en_attente']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-content">
                <h3>Articles dans le Panier</h3>
                <p class="stat-value"><?php echo $stats['articles_panier']; ?></p>
            </div>
        </div>
    </div>

    <!-- Dernières commandes -->
    <div class="dashboard-section">
        <h2>Mes Dernières Commandes</h2>
        <div class="table-responsive">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Bétail</th>
                        <th>Vendeur</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($commande = $commandes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                            <td>
                                <div class="item-info">
                                    <img src="<?php echo htmlspecialchars($commande['photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($commande['nom_betail']); ?>"
                                         class="item-thumbnail">
                                    <span><?php echo htmlspecialchars($commande['nom_betail']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($commande['vendeur_nom']); ?></td>
                            <td><?php echo number_format($commande['prix'], 0, ',', ' '); ?> FCFA</td>
                            <td>
                                <span class="status-badge status-<?php echo $commande['statut']; ?>">
                                    <?php echo ucfirst($commande['statut']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="detail_commande.php?id=<?php echo $commande['id']; ?>" 
                                   class="btn btn-small btn-secondary">
                                    Voir détails
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panier -->
    <div class="dashboard-section">
        <h2>Mon Panier</h2>
        <?php if ($panier->num_rows > 0): ?>
            <div class="panier-grid">
                <?php while ($article = $panier->fetch_assoc()): ?>
                    <div class="betail-card">
                        <div class="betail-image">
                            <img src="<?php echo htmlspecialchars($article['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($article['nom_betail']); ?>">
                        </div>
                        <div class="betail-info">
                            <h3><?php echo htmlspecialchars($article['nom_betail']); ?></h3>
                            <p class="vendor">Vendeur: <?php echo htmlspecialchars($article['vendeur_nom']); ?></p>
                            <p class="price"><?php echo number_format($article['prix'], 0, ',', ' '); ?> FCFA</p>
                            <div class="betail-actions">
                                <a href="commander.php?id=<?php echo $article['betail_id']; ?>" 
                                   class="btn btn-primary">Commander</a>
                                <button onclick="retirerDuPanier(<?php echo $article['id']; ?>)" 
                                        class="btn btn-danger">Retirer</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Votre panier est vide</p>
                <a href="rechercher.php" class="btn btn-primary">Parcourir les bétails</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function retirerDuPanier(id) {
    if (confirm('Êtes-vous sûr de vouloir retirer cet article du panier ?')) {
        fetch('api/retirer_panier.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erreur lors du retrait du panier');
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
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.dashboard-header h1 {
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

.dashboard-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
}

.dashboard-section h2 {
    font-size: 1.5rem;
    color: var(--text-color);
    margin-bottom: 1.5rem;
}

.item-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.item-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
}

.panier-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-state p {
    color: var(--text-light);
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-section {
        padding: 1rem;
    }
    
    .panier-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
