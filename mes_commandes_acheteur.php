<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté et est un acheteur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    header('Location: connexion.php');
    exit();
}

$acheteur_id = $_SESSION['user_id'];
$conn = connectDB();

// Récupérer les commandes de l'acheteur
$stmt = $conn->prepare("
    SELECT c.*, b.nom_betail, b.prix, u.nom as vendeur_nom, u.telephone as vendeur_telephone
    FROM commandes c
    JOIN betail b ON c.betail_id = b.id
    JOIN users u ON b.vendeur_id = u.id
    WHERE c.acheteur_id = ?
    ORDER BY c.date_commande DESC
");
$stmt->bind_param("i", $acheteur_id);
$stmt->execute();
$commandes = $stmt->get_result();

// Gestion de l'annulation de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'annuler_commande') {
    try {
        $commande_id = $_POST['commande_id'];
        
        // Vérifier que la commande appartient bien à l'acheteur
        $stmt = $conn->prepare("
            UPDATE commandes 
            SET statut = 'annule' 
            WHERE id = ? AND acheteur_id = ? AND statut = 'en_attente'
        ");
        
        $stmt->bind_param("ii", $commande_id, $acheteur_id);
        
        if ($stmt->execute()) {
            $success = "Commande annulée avec succès";
        } else {
            $error = "Erreur lors de l'annulation de la commande";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container">
    <h1>Mes Commandes</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="commandes-grid">
        <?php while ($commande = $commandes->fetch_assoc()): ?>
            <div class="commande-card">
                <div class="commande-header">
                    <h3>Commande #<?php echo $commande['id']; ?></h3>
                    <span class="statut <?php echo $commande['statut']; ?>">
                        <?php 
                        switch($commande['statut']) {
                            case 'en_attente':
                                echo 'En attente';
                                break;
                            case 'paye':
                                echo 'Payée';
                                break;
                            case 'livre':
                                echo 'Livrée';
                                break;
                            case 'annule':
                                echo 'Annulée';
                                break;
                        }
                        ?>
                    </span>
                </div>
                
                <div class="commande-details">
                    <p><strong>Bétail:</strong> <?php echo htmlspecialchars($commande['nom_betail']); ?></p>
                    <p><strong>Prix:</strong> <?php echo number_format($commande['prix'], 0, ',', ' '); ?> FCFA</p>
                    <p><strong>Vendeur:</strong> <?php echo htmlspecialchars($commande['vendeur_nom']); ?></p>
                    <p><strong>Téléphone du vendeur:</strong> <?php echo htmlspecialchars($commande['vendeur_telephone']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></p>
                    <p><strong>Méthode de paiement:</strong> 
                        <?php 
                        switch($commande['methode_paiement']) {
                            case 'wave':
                                echo 'Wave';
                                break;
                            case 'orange_money':
                                echo 'Orange Money';
                                break;
                            case 'livraison':
                                echo 'Paiement à la livraison';
                                break;
                        }
                        ?>
                    </p>
                </div>
                
                <div class="commande-actions">
                    <?php if ($commande['statut'] === 'en_attente'): ?>
                        <form method="POST" action="" class="annuler-form">
                            <input type="hidden" name="action" value="annuler_commande">
                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ?')">
                                Annuler la commande
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.commandes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.commande-card {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: var(--card-shadow);
}

.commande-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.commande-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.statut {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.statut.en_attente {
    background: #fff3cd;
    color: #856404;
}

.statut.paye {
    background: #d4edda;
    color: #155724;
}

.statut.livre {
    background: #cce5ff;
    color: #004085;
}

.statut.annule {
    background: #f8d7da;
    color: #721c24;
}

.commande-details {
    margin-bottom: 1.5rem;
}

.commande-details p {
    margin: 0.5rem 0;
    color: var(--text-light);
}

.commande-details strong {
    color: var(--text-dark);
    margin-right: 0.5rem;
}

.commande-actions {
    margin-top: 1rem;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
}

.btn-danger:hover {
    background-color: #c82333;
}

@media (max-width: 768px) {
    .commandes-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?> 