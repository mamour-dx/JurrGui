<?php
require_once 'includes/header.php';

// Vérification que l'utilisateur est connecté et est un vendeur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendeur') {
    header('Location: connexion.php');
    exit();
}

$conn = connectDB();

// Récupération des annonces du vendeur
$stmt = $conn->prepare("
    SELECT b.*, 
           COUNT(DISTINCT c.id) as nombre_ventes,
           AVG(a.note) as note_moyenne
    FROM betail b 
    LEFT JOIN commandes c ON b.id = c.betail_id AND c.statut = 'paye'
    LEFT JOIN avis a ON a.vendeur_id = b.vendeur_id
    WHERE b.vendeur_id = ?
    GROUP BY b.id
    ORDER BY b.date_publication DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$annonces = $stmt->get_result();

// Récupération des commandes en cours
$stmt = $conn->prepare("
    SELECT c.*, b.nom_betail, u.nom as acheteur_nom, u.email as acheteur_email
    FROM commandes c
    JOIN betail b ON c.betail_id = b.id
    JOIN users u ON c.acheteur_id = u.id
    WHERE b.vendeur_id = ? AND c.statut = 'en_attente'
    ORDER BY c.date_commande DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$commandes = $stmt->get_result();
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h2>Dashboard Vendeur</h2>
        <a href="ajouter_betail.php" class="btn btn-primary">Ajouter une annonce</a>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Annonces actives</h3>
            <p><?php echo $annonces->num_rows; ?></p>
        </div>
        <div class="stat-card">
            <h3>Commandes en attente</h3>
            <p><?php echo $commandes->num_rows; ?></p>
        </div>
        <!-- Autres statistiques... -->
    </div>
    
    <div class="dashboard-content">
        <section class="commandes-section">
            <h3>Commandes en attente</h3>
            <?php if ($commandes->num_rows > 0): ?>
                <div class="commandes-list">
                    <?php while ($commande = $commandes->fetch_assoc()): ?>
                        <div class="commande-card">
                            <div class="commande-info">
                                <h4><?php echo htmlspecialchars($commande['nom_betail']); ?></h4>
                                <p>Acheteur: <?php echo htmlspecialchars($commande['acheteur_nom']); ?></p>
                                <p>Email: <?php echo htmlspecialchars($commande['acheteur_email']); ?></p>
                                <p>Date: <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></p>
                            </div>
                            <div class="commande-actions">
                                <button class="btn btn-success" onclick="confirmerPaiement(<?php echo $commande['id']; ?>)">
                                    Confirmer paiement
                                </button>
                                <button class="btn btn-danger" onclick="annulerCommande(<?php echo $commande['id']; ?>)">
                                    Annuler
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Aucune commande en attente</p>
            <?php endif; ?>
        </section>
        
        <section class="annonces-section">
            <h3>Mes annonces</h3>
            <div class="annonces-grid">
                <?php while ($annonce = $annonces->fetch_assoc()): ?>
                    <div class="annonce-card">
                        <img src="<?php echo htmlspecialchars($annonce['photo']); ?>" alt="<?php echo htmlspecialchars($annonce['nom_betail']); ?>">
                        <div class="annonce-content">
                            <h4><?php echo htmlspecialchars($annonce['nom_betail']); ?></h4>
                            <p class="price"><?php echo number_format($annonce['prix'], 0, ',', ' '); ?> FCFA</p>
                            <p class="stats">
                                Ventes: <?php echo $annonce['nombre_ventes']; ?>
                                <?php if ($annonce['note_moyenne']): ?>
                                    | Note: <?php echo number_format($annonce['note_moyenne'], 1); ?> ⭐
                                <?php endif; ?>
                            </p>
                            <div class="annonce-actions">
                                <a href="modifier_betail.php?id=<?php echo $annonce['id']; ?>" class="btn btn-secondary">Modifier</a>
                                <button class="btn btn-danger" onclick="supprimerAnnonce(<?php echo $annonce['id']; ?>)">Supprimer</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>
</div>

<script>
function confirmerPaiement(commandeId) {
    if (confirm('Confirmer la réception du paiement ?')) {
        fetch('api/confirmer_paiement.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ commande_id: commandeId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la confirmation');
            }
        });
    }
}

function annulerCommande(commandeId) {
    if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
        fetch('api/annuler_commande.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ commande_id: commandeId })
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

function supprimerAnnonce(annonceId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?')) {
        fetch('api/supprimer_annonce.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ annonce_id: annonceId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression');
            }
        });
    }
}
</script>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>
