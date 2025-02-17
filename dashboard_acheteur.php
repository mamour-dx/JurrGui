<?php
require_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    header('Location: connexion.php');
    exit();
}

$conn = connectDB();

// Récupération des commandes
$stmt = $conn->prepare("
    SELECT c.*, b.nom_betail, b.photo, u.nom as vendeur_nom, u.id as vendeur_id,
           (SELECT note FROM avis WHERE acheteur_id = ? AND vendeur_id = u.id LIMIT 1) as avis_donne
    FROM commandes c
    JOIN betail b ON c.betail_id = b.id
    JOIN users u ON b.vendeur_id = u.id
    WHERE c.acheteur_id = ?
    ORDER BY c.date_commande DESC
");
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$commandes = $stmt->get_result();

// Récupération des statistiques
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_commandes,
        SUM(CASE WHEN statut = 'livre' THEN 1 ELSE 0 END) as commandes_livrees,
        SUM(montant) as total_depense
    FROM commandes 
    WHERE acheteur_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>

<div class="dashboard-container">
    <h1>Mon Tableau de Bord</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
                <h3>Total Commandes</h3>
                <p><?php echo $stats['total_commandes']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3>Commandes Livrées</h3>
                <p><?php echo $stats['commandes_livrees']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>Total Dépensé</h3>
                <p><?php echo number_format($stats['total_depense'], 0, ',', ' '); ?> FCFA</p>
            </div>
        </div>
    </div>
    
    <div class="commandes-section">
        <h2>Mes Commandes</h2>
        
        <div class="commandes-grid">
            <?php while ($commande = $commandes->fetch_assoc()): ?>
                <div class="commande-card">
                    <div class="commande-header">
                        <img src="<?php echo htmlspecialchars($commande['photo']); ?>" 
                             alt="<?php echo htmlspecialchars($commande['nom_betail']); ?>">
                        <div class="commande-status <?php echo $commande['statut']; ?>">
                            <?php echo ucfirst($commande['statut']); ?>
                        </div>
                    </div>
                    
                    <div class="commande-content">
                        <h3><?php echo htmlspecialchars($commande['nom_betail']); ?></h3>
                        <p class="vendeur">Vendeur: <?php echo htmlspecialchars($commande['vendeur_nom']); ?></p>
                        <p class="date">
                            Commandé le: <?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?>
                        </p>
                        <p class="prix">
                            <?php echo number_format($commande['montant'], 0, ',', ' '); ?> FCFA
                        </p>
                        
                        <?php if ($commande['statut'] === 'livre' && !$commande['avis_donne']): ?>
                            <button onclick="ouvrirModalAvis(
                                <?php echo $commande['vendeur_id']; ?>, 
                                '<?php echo htmlspecialchars($commande['vendeur_nom']); ?>'
                            )" class="btn btn-secondary">
                                Laisser un avis
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($commande['statut'] === 'en_attente'): ?>
                            <button onclick="annulerCommande(<?php echo $commande['id']; ?>)" 
                                    class="btn btn-danger">
                                Annuler
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Modal Avis -->
<div id="modal-avis" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Laisser un avis</h2>
        <form id="form-avis" onsubmit="soumettreAvis(event)">
            <input type="hidden" id="vendeur-id">
            
            <div class="form-group">
                <label>Note</label>
                <div class="rating">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?php echo $i; ?>" name="note" value="<?php echo $i; ?>">
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
function ouvrirModalAvis(vendeurId, vendeurNom) {
    document.getElementById('vendeur-id').value = vendeurId;
    document.getElementById('modal-avis').style.display = 'block';
}

function soumettreAvis(e) {
    e.preventDefault();
    
    const vendeurId = document.getElementById('vendeur-id').value;
    const note = document.querySelector('input[name="note"]:checked').value;
    const commentaire = document.getElementById('commentaire').value;
    
    fetch('api/ajouter_avis.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            vendeur_id: vendeurId,
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
    });
}

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
                alert('Erreur lors de l\'annulation');
            }
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
.dashboard-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    font-size: 2rem;
}

.commandes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.commande-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.commande-header {
    position: relative;
}

.commande-header img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.commande-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    color: white;
    font-size: 0.875rem;
}

.commande-status.en_attente { background: #f59e0b; }
.commande-status.paye { background: #3b82f6; }
.commande-status.livre { background: #10b981; }
.commande-status.annule { background: #ef4444; }

.commande-content {
    padding: 1.5rem;
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
    border-radius: 8px;
    position: relative;
}

.close {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
}

.rating {
    display: flex;
    flex-direction: row-reverse;
    gap: 0.5rem;
}

.rating input {
    display: none;
}

.rating label {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ddd;
}

.rating input:checked ~ label {
    color: #ffd700;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
