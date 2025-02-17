<?php
require_once 'includes/header.php';
$conn = connectDB();

// Paramètres de recherche
$search = isset($_GET['q']) ? clean($conn, $_GET['q']) : '';
$categorie = isset($_GET['categorie']) ? clean($conn, $_GET['categorie']) : '';
$prix_min = isset($_GET['prix_min']) ? floatval($_GET['prix_min']) : 0;
$prix_max = isset($_GET['prix_max']) ? floatval($_GET['prix_max']) : PHP_FLOAT_MAX;
$tri = isset($_GET['tri']) ? clean($conn, $_GET['tri']) : 'recent';

// Construction de la requête
$query = "SELECT b.*, u.nom as vendeur_nom, 
          (SELECT AVG(note) FROM avis WHERE vendeur_id = u.id) as note_vendeur
          FROM betail b 
          JOIN users u ON b.vendeur_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (b.nom_betail LIKE ? OR b.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($categorie)) {
    $query .= " AND b.categorie = ?";
    $params[] = $categorie;
    $types .= "s";
}

$query .= " AND b.prix BETWEEN ? AND ?";
$params[] = $prix_min;
$params[] = $prix_max;
$types .= "dd";

// Tri
switch ($tri) {
    case 'prix_asc':
        $query .= " ORDER BY b.prix ASC";
        break;
    case 'prix_desc':
        $query .= " ORDER BY b.prix DESC";
        break;
    case 'note':
        $query .= " ORDER BY note_vendeur DESC";
        break;
    default:
        $query .= " ORDER BY b.date_publication DESC";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultats = $stmt->get_result();
?>

<div class="search-container">
    <div class="search-filters">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <input type="text" name="q" placeholder="Rechercher..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Catégorie</label>
                <select name="categorie" class="form-control">
                    <option value="">Toutes les catégories</option>
                    <option value="bovins" <?php echo $categorie === 'bovins' ? 'selected' : ''; ?>>Bovins</option>
                    <option value="ovins" <?php echo $categorie === 'ovins' ? 'selected' : ''; ?>>Ovins</option>
                    <option value="caprins" <?php echo $categorie === 'caprins' ? 'selected' : ''; ?>>Caprins</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Prix</label>
                <div class="price-range">
                    <input type="number" name="prix_min" placeholder="Min" 
                           value="<?php echo $prix_min > 0 ? $prix_min : ''; ?>" class="form-control">
                    <span>à</span>
                    <input type="number" name="prix_max" placeholder="Max" 
                           value="<?php echo $prix_max < PHP_FLOAT_MAX ? $prix_max : ''; ?>" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label>Trier par</label>
                <select name="tri" class="form-control">
                    <option value="recent" <?php echo $tri === 'recent' ? 'selected' : ''; ?>>Plus récent</option>
                    <option value="prix_asc" <?php echo $tri === 'prix_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                    <option value="prix_desc" <?php echo $tri === 'prix_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                    <option value="note" <?php echo $tri === 'note' ? 'selected' : ''; ?>>Meilleure note</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="rechercher.php" class="btn btn-secondary">Réinitialiser</a>
        </form>
    </div>
    
    <div class="search-results">
        <h2>Résultats de recherche</h2>
        <p class="results-count"><?php echo $resultats->num_rows; ?> résultats trouvés</p>
        
        <div class="results-grid">
            <?php if ($resultats->num_rows > 0): ?>
                <?php while ($betail = $resultats->fetch_assoc()): ?>
                    <div class="betail-card" data-aos="fade-up">
                        <div class="betail-image">
                            <img src="<?php echo htmlspecialchars($betail['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($betail['nom_betail']); ?>">
                            <?php if ($betail['note_vendeur']): ?>
                                <div class="vendor-rating">
                                    ⭐ <?php echo number_format($betail['note_vendeur'], 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="betail-info">
                            <h3><?php echo htmlspecialchars($betail['nom_betail']); ?></h3>
                            <p class="category"><?php echo ucfirst($betail['categorie']); ?></p>
                            <p class="price"><?php echo number_format($betail['prix'], 0, ',', ' '); ?> FCFA</p>
                            <p class="vendor">Vendeur: <?php echo htmlspecialchars($betail['vendeur_nom']); ?></p>
                            
                            <div class="betail-actions">
                                <a href="detail_betail.php?id=<?php echo $betail['id']; ?>" 
                                   class="btn btn-primary">Voir détails</a>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'acheteur'): ?>
                                    <button onclick="ajouterAuPanier(<?php echo $betail['id']; ?>)" 
                                            class="btn btn-secondary">
                                        Ajouter au panier
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>Aucun résultat trouvé pour votre recherche.</p>
                    <p>Essayez de modifier vos critères de recherche.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function ajouterAuPanier(betailId) {
    fetch('api/ajouter_panier.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ betail_id: betailId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produit ajouté au panier !');
            // Mettre à jour le compteur du panier dans le header
            updateCartCount(data.cart_count);
        } else {
            alert(data.message || 'Erreur lors de l\'ajout au panier');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
}

// Animation au défilement avec AOS
document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
        duration: 800,
        offset: 100,
        once: true
    });
});
</script>

<style>
.search-container {
    display: flex;
    gap: 2rem;
    padding: 2rem;
}

.search-filters {
    flex: 0 0 300px;
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: fit-content;
}

.search-results {
    flex: 1;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.price-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.betail-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.betail-card:hover {
    transform: translateY(-5px);
}

.betail-image {
    position: relative;
    height: 200px;
}

.betail-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.vendor-rating {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.betail-info {
    padding: 1rem;
}

.betail-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.no-results {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .search-container {
        flex-direction: column;
    }
    
    .search-filters {
        flex: none;
        width: 100%;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
