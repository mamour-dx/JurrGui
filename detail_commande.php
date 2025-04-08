<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

// Vérifier si l'ID de la commande est fourni
if (!isset($_GET['id'])) {
    header('Location: dashboard_acheteur.php');
    exit();
}

$conn = connectDB();
$commande_id = $_GET['id'];

// Récupérer les détails de la commande
$query = "
    SELECT c.*, 
           GROUP_CONCAT(CONCAT(b.nom_betail, '|', ca.quantite, '|', ca.prix_unitaire, '|', u.nom) SEPARATOR '||') as articles_details
    FROM commandes c
    JOIN commande_articles ca ON c.id = ca.commande_id
    JOIN betail b ON ca.betail_id = b.id
    JOIN users u ON ca.vendeur_id = u.id
    WHERE c.id = ? AND c.acheteur_id = ?
    GROUP BY c.id
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $commande_id, $_SESSION['user_id']);
$stmt->execute();
$commande = $stmt->get_result()->fetch_assoc();

// Vérifier si la commande existe et appartient à l'utilisateur
if (!$commande) {
    header('Location: dashboard_acheteur.php');
    exit();
}

// Parser les articles
$articles = [];
if (!empty($commande['articles_details'])) {
    $articles_array = explode('||', $commande['articles_details']);
    foreach ($articles_array as $article) {
        list($nom, $quantite, $prix, $vendeur) = explode('|', $article);
        $articles[] = [
            'nom' => $nom,
            'quantite' => $quantite,
            'prix' => $prix,
            'vendeur' => $vendeur
        ];
    }
}
?>

<div class="detail-commande-container">
    <div class="detail-header">
        <h1>Détail de la Commande #<?php echo $commande_id; ?></h1>
        <a href="<?php echo $_SESSION['role'] === 'vendeur' ? 'mes_commandes_vendeur.php' : 'mes_commandes_acheteur.php'; ?>" class="btn btn-secondary">
            <span class="icon">←</span> Retour aux commandes
        </a>
    </div>

    <div class="detail-grid">
        <!-- Informations sur la commande -->
        <div class="detail-card commande-info">
            <h2>Informations de la Commande</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Statut</span>
                    <span class="value status-badge status-<?php echo $commande['statut']; ?>">
                        <?php echo ucfirst($commande['statut']); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="label">Date de commande</span>
                    <span class="value">
                        <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="label">Méthode de paiement</span>
                    <span class="value">
                        <?php echo ucfirst($commande['methode_paiement']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Liste des articles -->
        <div class="detail-card articles-info">
            <h2>Articles commandés</h2>
            <div class="articles-list">
                <?php foreach ($articles as $article): ?>
                    <div class="article-item">
                        <div class="article-header">
                            <h3><?php echo htmlspecialchars($article['nom']); ?></h3>
                            <span class="vendeur">Vendeur: <?php echo htmlspecialchars($article['vendeur']); ?></span>
                        </div>
                        <div class="article-details">
                            <span class="quantite">Quantité: <?php echo $article['quantite']; ?></span>
                            <span class="prix">Prix unitaire: <?php echo number_format($article['prix'], 0, ',', ' '); ?> FCFA</span>
                            <span class="total">Total: <?php echo number_format($article['prix'] * $article['quantite'], 0, ',', ' '); ?> FCFA</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Informations de livraison -->
        <div class="detail-card livraison-info">
            <h2>Informations de Livraison</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Nom</span>
                    <span class="value"><?php echo htmlspecialchars($commande['nom_livraison']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Téléphone</span>
                    <span class="value"><?php echo htmlspecialchars($commande['telephone_livraison']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Adresse</span>
                    <span class="value"><?php echo htmlspecialchars($commande['adresse_livraison']); ?></span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="detail-card actions-info">
            <h2>Actions</h2>
            <div class="actions-grid">
                <?php if ($commande['statut'] === 'en_attente'): ?>
                    <button onclick="annulerCommande()" class="btn btn-danger">
                        Annuler la commande
                    </button>
                <?php endif; ?>
                
                <a href="contact.php" class="btn btn-secondary">
                    Contacter le support
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function annulerCommande() {
    if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
        fetch('api/annuler_commande.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                commande_id: <?php echo $commande_id; ?>
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
.detail-commande-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.detail-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.detail-card h2 {
    color: #333;
    margin: 0 0 1rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
}

.info-grid {
    display: grid;
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-item .label {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.info-item .value {
    color: #333;
    font-weight: 500;
}

.articles-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.article-item {
    padding: 1rem;
    border: 1px solid #eee;
    border-radius: 5px;
}

.article-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.article-header h3 {
    margin: 0;
    color: #333;
}

.article-header .vendeur {
    color: #666;
    font-size: 0.9rem;
}

.article-details {
    display: flex;
    justify-content: space-between;
    color: #666;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.9rem;
    font-weight: 500;
}

.status-en_attente {
    background: #fff3cd;
    color: #856404;
}

.status-paye {
    background: #d4edda;
    color: #155724;
}

.status-livre {
    background: #cce5ff;
    color: #004085;
}

.status-annule {
    background: #f8d7da;
    color: #721c24;
}

.actions-grid {
    display: flex;
    gap: 1rem;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #007bff;
    color: #fff;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-danger {
    background: #dc3545;
    color: #fff;
}

.btn-danger:hover {
    background: #c82333;
}

.icon {
    margin-right: 0.5rem;
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?> 