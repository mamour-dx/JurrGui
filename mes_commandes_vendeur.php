<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté et est un vendeur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendeur') {
    header('Location: connexion.php');
    exit();
}

$vendeur_id = $_SESSION['user_id'];
$conn = connectDB();

// Récupérer les commandes du vendeur
$stmt = $conn->prepare("
    SELECT c.*, b.nom_betail, b.prix, u.nom as acheteur_nom, u.telephone as acheteur_telephone
    FROM commandes c
    JOIN betail b ON c.betail_id = b.id
    JOIN users u ON c.acheteur_id = u.id
    WHERE b.vendeur_id = ?
    ORDER BY c.date_commande DESC
");
$stmt->bind_param("i", $vendeur_id);
$stmt->execute();
$commandes = $stmt->get_result();

// Gestion de la mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        $commande_id = $_POST['commande_id'];
        $nouveau_statut = $_POST['statut'];
        
        $stmt = $conn->prepare("
            UPDATE commandes 
            SET statut = ? 
            WHERE id = ? AND EXISTS (
                SELECT 1 FROM betail 
                WHERE id = commandes.betail_id 
                AND vendeur_id = ?
            )
        ");
        
        $stmt->bind_param("sii", $nouveau_statut, $commande_id, $vendeur_id);
        
        if ($stmt->execute()) {
            $success = "Statut de la commande mis à jour avec succès";
        } else {
            $error = "Erreur lors de la mise à jour du statut";
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
                    <p><strong>Acheteur:</strong> <?php echo htmlspecialchars($commande['acheteur_nom']); ?></p>
                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($commande['acheteur_telephone']); ?></p>
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
                    <form method="POST" action="" class="status-form">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                        
                        <select name="statut" class="form-select" onchange="this.form.submit()">
                            <option value="en_attente" <?php echo $commande['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                            <option value="paye" <?php echo $commande['statut'] === 'paye' ? 'selected' : ''; ?>>Payée</option>
                            <option value="livre" <?php echo $commande['statut'] === 'livre' ? 'selected' : ''; ?>>Livrée</option>
                            <option value="annule" <?php echo $commande['statut'] === 'annule' ? 'selected' : ''; ?>>Annulée</option>
                        </select>
                    </form>
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

.form-select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: white;
    cursor: pointer;
}

.form-select:focus {
    outline: none;
    border-color: var(--primary-color);
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