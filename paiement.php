<?php
require_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur' || empty($_SESSION['panier'])) {
    header('Location: panier.php');
    exit();
}

$conn = connectDB();

// Récupération des articles du panier
$ids = array_keys($_SESSION['panier']);
$ids_str = implode(',', array_fill(0, count($ids), '?'));

$stmt = $conn->prepare("
    SELECT b.*, u.nom as vendeur_nom 
    FROM betail b
    JOIN users u ON b.vendeur_id = u.id
    WHERE b.id IN ($ids_str)
");
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();
$result = $stmt->get_result();

$articles = [];
$total = 0;

while ($article = $result->fetch_assoc()) {
    $article['quantite'] = $_SESSION['panier'][$article['id']];
    $article['sous_total'] = $article['prix'] * $article['quantite'];
    $total += $article['sous_total'];
    $articles[] = $article;
}

$frais_service = $total * 0.02;
$total_final = $total + $frais_service;

// Génération d'un ID de commande unique
$commande_id = uniqid('CMD');
?>

<div class="paiement-container">
    <h1>Paiement</h1>
    
    <div class="paiement-grid">
        <div class="paiement-details">
            <div class="resume-commande">
                <h3>Résumé de la commande</h3>
                <div class="articles-liste">
                    <?php foreach ($articles as $article): ?>
                        <div class="article-resume">
                            <img src="<?php echo htmlspecialchars($article['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($article['nom_betail']); ?>">
                            <div class="article-info">
                                <h4><?php echo htmlspecialchars($article['nom_betail']); ?></h4>
                                <p>Vendeur: <?php echo htmlspecialchars($article['vendeur_nom']); ?></p>
                                <p>Quantité: <?php echo $article['quantite']; ?></p>
                                <p class="prix">
                                    <?php echo number_format($article['sous_total'], 0, ',', ' '); ?> FCFA
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="total-details">
                    <div class="ligne">
                        <span>Sous-total</span>
                        <span><?php echo number_format($total, 0, ',', ' '); ?> FCFA</span>
                    </div>
                    <div class="ligne">
                        <span>Frais de service (2%)</span>
                        <span><?php echo number_format($frais_service, 0, ',', ' '); ?> FCFA</span>
                    </div>
                    <div class="ligne total">
                        <span>Total à payer</span>
                        <span><?php echo number_format($total_final, 0, ',', ' '); ?> FCFA</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="methodes-paiement">
            <h3>Choisir le mode de paiement</h3>
            
            <div class="paiement-options">
                <div class="paiement-option" onclick="selectPaiement('wave')">
                    <img src="assets/images/wave.png" alt="Wave">
                    <span>Wave</span>
                </div>
                <div class="paiement-option" onclick="selectPaiement('orange_money')">
                    <img src="assets/images/orange-money.png" alt="Orange Money">
                    <span>Orange Money</span>
                </div>
            </div>
            
            <div id="wave-details" class="paiement-details-section" style="display: none;">
                <h4>Paiement via Wave</h4>
                <p>1. Envoyez le montant de <?php echo number_format($total_final, 0, ',', ' '); ?> FCFA au numéro:</p>
                <p class="numero">+221 77 XXX XX XX</p>
                <p>2. Notez votre numéro de transaction Wave</p>
                <input type="text" id="wave-transaction" placeholder="Numéro de transaction Wave" class="form-control">
            </div>
            
            <div id="om-details" class="paiement-details-section" style="display: none;">
                <h4>Paiement via Orange Money</h4>
                <p>1. Envoyez le montant de <?php echo number_format($total_final, 0, ',', ' '); ?> FCFA au numéro:</p>
                <p class="numero">+221 77 XXX XX XX</p>
                <p>2. Notez votre numéro de transaction Orange Money</p>
                <input type="text" id="om-transaction" placeholder="Numéro de transaction OM" class="form-control">
            </div>
            
            <button onclick="confirmerPaiement('<?php echo $commande_id; ?>')" 
                    class="btn btn-primary btn-payer" disabled>
                Confirmer le paiement
            </button>
        </div>
    </div>
</div>

<script>
let methodePaiement = null;

function selectPaiement(methode) {
    methodePaiement = methode;
    document.querySelectorAll('.paiement-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    
    document.getElementById('wave-details').style.display = methode === 'wave' ? 'block' : 'none';
    document.getElementById('om-details').style.display = methode === 'orange_money' ? 'block' : 'none';
    
    document.querySelector('.btn-payer').disabled = false;
}

function confirmerPaiement(commandeId) {
    const transactionId = methodePaiement === 'wave' 
        ? document.getElementById('wave-transaction').value 
        : document.getElementById('om-transaction').value;
        
    if (!transactionId) {
        alert('Veuillez entrer le numéro de transaction');
        return;
    }
    
    fetch('api/confirmer_paiement.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            commande_id: commandeId,
            methode_paiement: methodePaiement,
            transaction_id: transactionId,
            montant: <?php echo $total_final; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'confirmation_paiement.php?id=' + commandeId;
        } else {
            alert(data.message || 'Erreur lors du paiement');
        }
    });
}
</script>

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

.resume-commande {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.articles-liste {
    margin: 1rem 0;
}

.article-resume {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.article-resume img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.total-details {
    margin-top: 1rem;
}

.ligne {
    display: flex;
    justify-content: space-between;
    margin: 0.5rem 0;
}

.ligne.total {
    font-weight: bold;
    font-size: 1.2rem;
    border-top: 1px solid var(--border-color);
    padding-top: 1rem;
    margin-top: 1rem;
}

.methodes-paiement {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.paiement-options {
    display: flex;
    gap: 1rem;
    margin: 1rem 0;
}

.paiement-option {
    flex: 1;
    padding: 1rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease;
}

.paiement-option.selected {
    border-color: var(--primary-color);
    background: #f8f9fa;
}

.paiement-option img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    margin-bottom: 0.5rem;
}

.paiement-details-section {
    margin: 1.5rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.numero {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--primary-color);
    margin: 1rem 0;
}

.btn-payer {
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
