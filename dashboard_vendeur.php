<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté et est un vendeur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendeur') {
    header('Location: connexion.php');
    exit();
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Récupérer les statistiques du vendeur
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM betail WHERE vendeur_id = ?) as total_betail,
    (SELECT COUNT(*) FROM commandes c 
     JOIN betail b ON c.betail_id = b.id 
     WHERE b.vendeur_id = ? AND c.statut = 'paye') as ventes_completees,
    (SELECT COUNT(*) FROM commandes c 
     JOIN betail b ON c.betail_id = b.id 
     WHERE b.vendeur_id = ? AND c.statut = 'en_attente') as commandes_en_attente,
    (SELECT AVG(note) FROM avis WHERE vendeur_id = ?) as note_moyenne";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Récupérer les dernières commandes
$commandes_query = "SELECT c.*, b.nom_betail, b.prix, u.nom as acheteur_nom 
                   FROM commandes c 
                   JOIN betail b ON c.betail_id = b.id 
                   JOIN users u ON c.acheteur_id = u.id 
                   WHERE b.vendeur_id = ? 
                   ORDER BY c.date_commande DESC 
                   LIMIT 5";

$stmt = $conn->prepare($commandes_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$commandes = $stmt->get_result();

// Récupérer les bétails du vendeur
$betails_query = "SELECT * FROM betail WHERE vendeur_id = ? ORDER BY date_publication DESC";
$stmt = $conn->prepare($betails_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$betails = $stmt->get_result();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Tableau de bord Vendeur</h1>
        <a href="ajouter_betail.php" class="btn btn-primary">
            <span class="icon">➕</span> Ajouter un bétail
        </a>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🐮</div>
            <div class="stat-content">
                <h3>Total Bétail</h3>
                <p class="stat-value"><?php echo $stats['total_betail']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>Ventes Complétées</h3>
                <p class="stat-value"><?php echo $stats['ventes_completees']; ?></p>
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
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
                <h3>Note Moyenne</h3>
                <p class="stat-value"><?php echo number_format($stats['note_moyenne'], 1); ?>/5</p>
            </div>
        </div>
    </div>

    <!-- Dernières commandes -->
    <div class="dashboard-section">
        <h2>Dernières Commandes</h2>
        <div class="table-responsive">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Bétail</th>
                        <th>Acheteur</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($commande = $commandes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                            <td><?php echo htmlspecialchars($commande['nom_betail']); ?></td>
                            <td><?php echo htmlspecialchars($commande['acheteur_nom']); ?></td>
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

    <!-- Liste des bétails -->
    <div class="dashboard-section">
        <h2>Mes Bétails</h2>
        <div class="betail-grid">
            <?php while ($betail = $betails->fetch_assoc()): ?>
                <div class="betail-card">
                    <div class="betail-image">
                        <img src="<?php echo htmlspecialchars($betail['photo']); ?>" 
                             alt="<?php echo htmlspecialchars($betail['nom_betail']); ?>">
                    </div>
                    <div class="betail-info">
                        <h3><?php echo htmlspecialchars($betail['nom_betail']); ?></h3>
                        <p class="category"><?php echo ucfirst($betail['categorie']); ?></p>
                        <p class="price"><?php echo number_format($betail['prix'], 0, ',', ' '); ?> FCFA</p>
                        <div class="betail-actions">
                            <a href="modifier_betail.php?id=<?php echo $betail['id']; ?>" 
                               class="btn btn-secondary">Modifier</a>
                            <button onclick="supprimerBetail(<?php echo $betail['id']; ?>)" 
                                    class="btn btn-danger">Supprimer</button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
function supprimerBetail(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce bétail ?')) {
        fetch('api/supprimer_betail.php', {
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
                alert(data.message || 'Erreur lors de la suppression');
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

.table-responsive {
    overflow-x: auto;
}

.dashboard-table {
    width: 100%;
    border-collapse: collapse;
}

.dashboard-table th,
.dashboard-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.dashboard-table th {
    background-color: var(--background-color);
    font-weight: 600;
    color: var(--text-color);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-en_attente {
    background-color: var(--warning-color);
    color: white;
}

.status-paye {
    background-color: var(--success-color);
    color: white;
}

.status-livre {
    background-color: var(--primary-color);
    color: white;
}

.status-annule {
    background-color: var(--error-color);
    color: white;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.betail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.btn-danger {
    background-color: var(--error-color);
    color: white;
}

.btn-danger:hover {
    background-color: #dc2626;
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
    
    .betail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
