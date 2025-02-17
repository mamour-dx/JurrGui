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
    SELECT c.*, b.nom_betail, b.photo, b.description, b.prix,
           u.nom as vendeur_nom, u.telephone as vendeur_telephone,
           (SELECT note FROM avis WHERE acheteur_id = ? AND vendeur_id = u.id LIMIT 1) as avis_donne
    FROM commandes c
    JOIN betail b ON c.betail_id = b.id
    JOIN users u ON b.vendeur_id = u.id
    WHERE c.id = ? AND c.acheteur_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $_SESSION['user_id'], $commande_id, $_SESSION['user_id']);
$stmt->execute();
$commande = $stmt->get_result()->fetch_assoc();

// Vérifier si la commande existe et appartient à l'utilisateur
if (!$commande) {
    header('Location: dashboard_acheteur.php');
    exit();
}
?>

<div class="detail-commande-container">
    <div class="detail-header">
        <h1>Détail de la Commande #<?php echo $commande_id; ?></h1>
        <a href="dashboard_acheteur.php" class="btn btn-secondary">
            <span class="icon">←</span> Retour au tableau de bord
        </a>
    </div>

    <div class="detail-grid">
        <!-- Informations sur le bétail -->
        <div class="detail-card betail-info">
            <div class="betail-image">
                <img src="<?php echo htmlspecialchars($commande['photo']); ?>" 
                     alt="<?php echo htmlspecialchars($commande['nom_betail']); ?>">
            </div>
            <div class="betail-details">
                <h2><?php echo htmlspecialchars($commande['nom_betail']); ?></h2>
                <p class="description"><?php echo htmlspecialchars($commande['description']); ?></p>
                <p class="price"><?php echo number_format($commande['prix'], 0, ',', ' '); ?> FCFA</p>
            </div>
        </div>

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
                <?php if ($commande['date_livraison']): ?>
                <div class="info-item">
                    <span class="label">Date de livraison</span>
                    <span class="value">
                        <?php echo date('d/m/Y H:i', strtotime($commande['date_livraison'])); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informations sur le vendeur -->
        <div class="detail-card vendeur-info">
            <h2>Informations du Vendeur</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Nom</span>
                    <span class="value"><?php echo htmlspecialchars($commande['vendeur_nom']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Téléphone</span>
                    <span class="value"><?php echo htmlspecialchars($commande['vendeur_telephone']); ?></span>
                </div>
            </div>

            <?php if ($commande['statut'] === 'livre' && !$commande['avis_donne']): ?>
            <button onclick="ouvrirModalAvis()" class="btn btn-primary mt-3">
                Laisser un avis
            </button>
            <?php endif; ?>
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
                
                <a href="contact.php?vendeur=<?php echo $commande['vendeur_id']; ?>" 
                   class="btn btn-secondary">
                    Contacter le vendeur
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Avis -->
<div id="modal-avis" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Laisser un avis pour <?php echo htmlspecialchars($commande['vendeur_nom']); ?></h2>
        <form id="form-avis" onsubmit="soumettreAvis(event)">
            <div class="form-group">
                <label>Note</label>
                <div class="rating">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="star<?php echo $i; ?>" name="note" value="<?php echo $i; ?>" required>
                    <label for="star<?php echo $i; ?>">⭐</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="commentaire">Commentaire</label>
                <textarea id="commentaire" name="commentaire" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Envoyer</button>
        </form>
    </div>
</div>

<script>
function ouvrirModalAvis() {
    document.getElementById('modal-avis').style.display = 'block';
}

function soumettreAvis(e) {
    e.preventDefault();
    
    const note = document.querySelector('input[name="note"]:checked').value;
    const commentaire = document.getElementById('commentaire').value;
    
    fetch('api/ajouter_avis.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            vendeur_id: <?php echo $commande['vendeur_id']; ?>,
            note: note,
            commentaire: commentaire
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Avis ajouté avec succès !');
            location.reload();
        } else {
            alert(data.message || 'Erreur lors de l\'ajout de l\'avis');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
}

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
                alert('Commande annulée avec succès !');
                window.location.href = 'dashboard_acheteur.php';
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

// Fermeture du modal
document.querySelector('.close').onclick = function() {
    document.getElementById('modal-avis').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modal-avis')) {
        document.getElementById('modal-avis').style.display = 'none';
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

.detail-header h1 {
    font-size: 2rem;
    color: var(--text-color);
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

.detail-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--card-shadow);
}

.betail-info {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
}

.betail-image img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 12px;
}

.betail-details h2 {
    font-size: 1.8rem;
    color: var(--text-color);
    margin-bottom: 1rem;
}

.betail-details .description {
    color: var(--text-light);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.betail-details .price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.info-grid {
    display: grid;
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item .label {
    font-size: 0.9rem;
    color: var(--text-light);
}

.info-item .value {
    font-size: 1.1rem;
    color: var(--text-color);
}

.actions-grid {
    display: grid;
    gap: 1rem;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.status-en_attente { 
    background: var(--warning-light);
    color: var(--warning-color);
}

.status-badge.status-paye { 
    background: var(--info-light);
    color: var(--info-color);
}

.status-badge.status-livre { 
    background: var(--success-light);
    color: var(--success-color);
}

.status-badge.status-annule { 
    background: var(--danger-light);
    color: var(--danger-color);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background: white;
    margin: 15% auto;
    padding: 2rem;
    width: 90%;
    max-width: 500px;
    border-radius: 16px;
    position: relative;
}

.close {
    position: absolute;
    right: 1.5rem;
    top: 1.5rem;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-light);
    transition: var(--transition-base);
}

.close:hover {
    color: var(--text-color);
}

.rating {
    display: flex;
    flex-direction: row-reverse;
    gap: 0.5rem;
    margin: 1rem 0;
}

.rating input {
    display: none;
}

.rating label {
    cursor: pointer;
    font-size: 2rem;
    color: #ddd;
    transition: var(--transition-base);
}

.rating label:hover,
.rating label:hover ~ label,
.rating input:checked ~ label {
    color: #ffd700;
}

@media (max-width: 992px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .betail-info {
        grid-template-columns: 1fr;
    }
    
    .betail-image img {
        height: 250px;
    }
}

@media (max-width: 768px) {
    .detail-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .detail-card {
        padding: 1.5rem;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?> 