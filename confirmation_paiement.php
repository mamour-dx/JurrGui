<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté et est un acheteur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    header('Location: connexion.php');
    exit();
}

// Vérifier si l'ID de la commande est fourni
if (!isset($_GET['id'])) {
    header('Location: dashboard_acheteur.php');
    exit();
}

$commande_id = $_GET['id'];
$conn = connectDB();

// Gestion de la soumission du formulaire de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();
        
        // Vérifier que la commande existe et appartient à l'utilisateur
        $stmt = $conn->prepare("
            SELECT id, methode_paiement, statut 
            FROM commandes 
            WHERE id = ? AND acheteur_id = ?
        ");
        $stmt->bind_param("ii", $commande_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $commande = $result->fetch_assoc();
        
        if (!$commande) {
            throw new Exception('Commande non trouvée');
        }
        
        if ($commande['statut'] !== 'en_attente') {
            throw new Exception('Cette commande ne peut plus être payée');
        }
        
        // Mettre à jour le statut de la commande
        $stmt = $conn->prepare("
            UPDATE commandes 
            SET statut = 'paye',
                date_modification = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $commande_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Erreur lors de la mise à jour du statut');
        }
        
        // Enregistrer la transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions (
                commande_id,
                montant,
                methode_paiement,
                transaction_id,
                date_creation
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        
        // Générer un ID de transaction unique
        $transaction_id = uniqid('TRX_');
        
        // Récupérer le montant total de la commande
        $stmt2 = $conn->prepare("
            SELECT SUM(quantite * prix_unitaire) as total
            FROM commande_articles
            WHERE commande_id = ?
        ");
        $stmt2->bind_param("i", $commande_id);
        $stmt2->execute();
        $total = $stmt2->get_result()->fetch_assoc()['total'];
        
        $stmt->bind_param(
            "idss",
            $commande_id,
            $total,
            $commande['methode_paiement'],
            $transaction_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Erreur lors de l\'enregistrement de la transaction');
        }
        
        // Notifier les vendeurs
        $stmt = $conn->prepare("
            SELECT DISTINCT vendeur_id 
            FROM commande_articles 
            WHERE commande_id = ?
        ");
        $stmt->bind_param("i", $commande_id);
        $stmt->execute();
        $vendeurs = $stmt->get_result();
        
        while ($vendeur = $vendeurs->fetch_assoc()) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    user_id,
                    type,
                    message,
                    lien
                ) VALUES (?, 'paiement_confirme', ?, ?)
            ");
            
            $message = "Le paiement de la commande #$commande_id a été confirmé";
            $lien = "detail_commande.php?id=$commande_id";
            
            $stmt->bind_param("iss", $vendeur['vendeur_id'], $message, $lien);
            $stmt->execute();
        }
        
        $conn->commit();
        
        // Rediriger vers la page de détail de la commande
        header("Location: detail_commande.php?id=" . $commande_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Récupérer les détails de la commande
$stmt = $conn->prepare("
    SELECT c.*, 
           GROUP_CONCAT(CONCAT(b.nom_betail, ' (', ca.quantite, ')') SEPARATOR ', ') as articles,
           SUM(ca.quantite * ca.prix_unitaire) as total
    FROM commandes c
    JOIN commande_articles ca ON c.id = ca.commande_id
    JOIN betail b ON ca.betail_id = b.id
    WHERE c.id = ? AND c.acheteur_id = ?
    GROUP BY c.id
");

$stmt->bind_param("ii", $commande_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$commande = $result->fetch_assoc();

if (!$commande) {
    header('Location: dashboard_acheteur.php');
    exit();
}
?>

<div class="confirmation-paiement-container">
    <h1>Confirmation de paiement</h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="commande-details">
        <div class="detail-card">
            <h2>Détails de la commande</h2>
            <p><strong>Commande #<?php echo $commande_id; ?></strong></p>
            <p><strong>Articles:</strong> <?php echo htmlspecialchars($commande['articles']); ?></p>
            <p><strong>Total:</strong> <?php echo number_format($commande['total'], 0, ',', ' '); ?> FCFA</p>
            <p><strong>Méthode de paiement:</strong> <?php echo ucfirst($commande['methode_paiement']); ?></p>
        </div>
        
        <div class="detail-card">
            <h2>Informations de livraison</h2>
            <p><strong>Nom:</strong> <?php echo htmlspecialchars($commande['nom']); ?></p>
            <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($commande['telephone']); ?></p>
            <p><strong>Adresse:</strong> <?php echo htmlspecialchars($commande['adresse']); ?></p>
        </div>
    </div>
    
    <div class="paiement-form">
        <h2>Confirmer le paiement</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="numero">Numéro de téléphone pour le paiement</label>
                <input type="tel" id="numero" name="numero" required 
                       placeholder="Entrez votre numéro <?php echo $commande['methode_paiement'] === 'wave' ? 'Wave' : 'Orange Money'; ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    Confirmer le paiement
                </button>
                <a href="mes_commandes.php" class="btn btn-secondary">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.confirmation-paiement-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.commande-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.detail-card {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: var(--card-shadow);
}

.detail-card h2 {
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.detail-card p {
    margin-bottom: 0.5rem;
}

.paiement-form {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: var(--card-shadow);
}

.paiement-form h2 {
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .commande-details {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
