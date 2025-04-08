<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté et est un acheteur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    header('Location: connexion.php');
    exit();
}

$conn = connectDB();

// Récupérer les articles du panier avec les informations des vendeurs
$stmt = $conn->prepare("
    SELECT b.*, p.quantite, u.nom as vendeur_nom, u.telephone as vendeur_telephone
    FROM panier p
    JOIN betail b ON p.betail_id = b.id
    JOIN users u ON b.vendeur_id = u.id
    WHERE p.acheteur_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$articles = [];
$total = 0;
$vendeurs = [];

while ($article = $result->fetch_assoc()) {
    $article['sous_total'] = $article['prix'] * $article['quantite'];
    $total += $article['sous_total'];
    $articles[] = $article;
    
    // Regrouper les articles par vendeur
    if (!isset($vendeurs[$article['vendeur_id']])) {
        $vendeurs[$article['vendeur_id']] = [
            'nom' => $article['vendeur_nom'],
            'telephone' => $article['vendeur_telephone'],
            'total' => 0,
            'articles' => []
        ];
    }
    $vendeurs[$article['vendeur_id']]['total'] += $article['sous_total'];
    $vendeurs[$article['vendeur_id']]['articles'][] = $article;
}

$frais_service = $total * 0.02;
$total_final = $total + $frais_service;
?>

<div class="paiement-container">
    <h1>Paiement de la commande</h1>
    
    <div class="paiement-grid">
        <!-- Résumé de la commande -->
        <div class="resume-section">
            <div class="card">
                <h2>Résumé de la commande</h2>
                <?php foreach ($vendeurs as $vendeur): ?>
                    <div class="vendeur-section">
                        <h3>Vendeur: <?php echo htmlspecialchars($vendeur['nom']); ?></h3>
                        <p class="vendeur-contact">
                            <strong>Téléphone pour le paiement:</strong> 
                            <span class="telephone"><?php echo htmlspecialchars($vendeur['telephone']); ?></span>
                        </p>
                        
                        <div class="articles-list">
                            <?php foreach ($vendeur['articles'] as $article): ?>
                                <div class="article-item">
                                    <div class="article-image">
                                        <img src="<?php echo htmlspecialchars($article['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($article['nom_betail']); ?>">
                                    </div>
                                    <div class="article-details">
                                        <h4><?php echo htmlspecialchars($article['nom_betail']); ?></h4>
                                        <p>Quantité: <?php echo $article['quantite']; ?></p>
                                        <p>Prix unitaire: <?php echo number_format($article['prix'], 0, ',', ' '); ?> FCFA</p>
                                        <p class="sous-total">
                                            Sous-total: <?php echo number_format($article['sous_total'], 0, ',', ' '); ?> FCFA
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="vendeur-total">
                                Total à payer au vendeur: 
                                <strong><?php echo number_format($vendeur['total'], 0, ',', ' '); ?> FCFA</strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="total-section">
                    <div class="ligne">
                        <span>Sous-total</span>
                        <span><?php echo number_format($total, 0, ',', ' '); ?> FCFA</span>
                    </div>
                    <div class="ligne">
                        <span>Frais de service (2%)</span>
                        <span><?php echo number_format($frais_service, 0, ',', ' '); ?> FCFA</span>
                    </div>
                    <div class="ligne total">
                        <span>Total</span>
                        <span><?php echo number_format($total_final, 0, ',', ' '); ?> FCFA</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Instructions de paiement -->
        <div class="instructions-section">
            <div class="card">
                <h2>Instructions de paiement</h2>
                
                <div class="methodes-paiement">
                    <div class="methode-item">
                        <h3>Paiement par Wave</h3>
                        <ol>
                            <li>Ouvrez votre application Wave</li>
                            <li>Sélectionnez "Envoyer de l'argent"</li>
                            <li>Entrez le numéro du vendeur indiqué ci-dessus</li>
                            <li>Entrez le montant exact indiqué pour ce vendeur</li>
                            <li>Validez le paiement</li>
                            <li>Conservez votre reçu de transaction</li>
                        </ol>
                    </div>
                    
                    <div class="methode-item">
                        <h3>Paiement par Orange Money</h3>
                        <ol>
                            <li>Composez *144#</li>
                            <li>Sélectionnez "Transfert d'argent"</li>
                            <li>Entrez le numéro du vendeur indiqué ci-dessus</li>
                            <li>Entrez le montant exact indiqué pour ce vendeur</li>
                            <li>Validez avec votre code secret</li>
                            <li>Conservez votre reçu de transaction</li>
                        </ol>
                    </div>
                </div>
                
                <div class="important-notice">
                    <h3>⚠️ Important</h3>
                    <ul>
                        <li>Effectuez un paiement séparé pour chaque vendeur</li>
                        <li>Utilisez exactement le même montant indiqué</li>
                        <li>Gardez vos reçus de transaction</li>
                        <li>En cas de problème, contactez le vendeur directement</li>
                    </ul>
                </div>
                
                <form id="confirmationForm" action="confirmation_paiement.php" method="POST">
                    <input type="hidden" name="commande_id" value="<?php echo uniqid(); ?>">
                    <div class="form-group">
                        <label for="methode_paiement">Méthode de paiement utilisée</label>
                        <select id="methode_paiement" name="methode_paiement" class="form-control" required>
                            <option value="">Choisir une méthode</option>
                            <option value="wave">Wave</option>
                            <option value="orange_money">Orange Money</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_id">Numéro(s) de transaction</label>
                        <input type="text" id="transaction_id" name="transaction_id" 
                               class="form-control" required
                               placeholder="Ex: W123456, W123457">
                        <small class="form-text">Si plusieurs paiements, séparez les numéros par des virgules</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        J'ai effectué le paiement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.paiement-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.paiement-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
}

.card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.vendeur-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--border-color);
}

.vendeur-section:last-child {
    border-bottom: none;
}

.vendeur-contact {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    margin: 1rem 0;
}

.telephone {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--primary-color);
}

.article-item {
    display: grid;
    grid-template-columns: 80px 1fr;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.article-image img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.vendeur-total {
    text-align: right;
    padding: 1rem 0;
    font-size: 1.1rem;
}

.total-section {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 2px solid var(--border-color);
}

.ligne {
    display: flex;
    justify-content: space-between;
    margin: 0.5rem 0;
}

.ligne.total {
    font-weight: bold;
    font-size: 1.2rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.methodes-paiement {
    margin: 2rem 0;
}

.methode-item {
    margin-bottom: 2rem;
}

.methode-item h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.methode-item ol {
    padding-left: 1.5rem;
}

.methode-item li {
    margin-bottom: 0.5rem;
}

.important-notice {
    background: #fff3cd;
    padding: 1rem;
    border-radius: 4px;
    margin: 2rem 0;
}

.important-notice h3 {
    color: #856404;
    margin-bottom: 1rem;
}

.important-notice ul {
    padding-left: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
}

.btn-block {
    width: 100%;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .paiement-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
