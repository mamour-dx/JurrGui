<?php
require_once '../includes/header.php';

// Vérification que l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

$conn = connectDB();

// Statistiques globales
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role != 'admin') as total_users,
        (SELECT COUNT(*) FROM users WHERE role = 'vendeur') as total_vendeurs,
        (SELECT COUNT(*) FROM users WHERE role = 'acheteur') as total_acheteurs,
        (SELECT COUNT(*) FROM betail) as total_annonces,
        (SELECT COUNT(*) FROM commandes WHERE statut = 'paye') as total_ventes,
        (SELECT SUM(montant) FROM commandes WHERE statut = 'paye') as chiffre_affaires
")->fetch_assoc();

// Dernières inscriptions
$recent_users = $conn->query("
    SELECT id, nom, email, role, date_creation
    FROM users 
    WHERE role != 'admin'
    ORDER BY date_creation DESC 
    LIMIT 5
");

// Dernières ventes
$recent_sales = $conn->query("
    SELECT c.*, b.nom_betail, u.nom as acheteur_nom, v.nom as vendeur_nom
    FROM commandes c
    JOIN betail b ON c.betail_id = b.id
    JOIN users u ON c.acheteur_id = u.id
    JOIN users v ON b.vendeur_id = v.id
    WHERE c.statut = 'paye'
    ORDER BY c.date_commande DESC
    LIMIT 5
");
?>

<div class="admin-dashboard">
    <div class="admin-header">
        <h1>Administration</h1>
        <div class="admin-actions">
            <a href="gerer_utilisateurs.php" class="btn btn-primary">Gérer les Utilisateurs</a>
            <a href="gerer_annonces.php" class="btn btn-primary">Gérer les Annonces</a>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>Utilisateurs</h3>
                <p><?php echo $stats['total_users']; ?></p>
                <small>
                    <?php echo $stats['total_vendeurs']; ?> vendeurs, 
                    <?php echo $stats['total_acheteurs']; ?> acheteurs
                </small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📢</div>
            <div class="stat-content">
                <h3>Annonces</h3>
                <p><?php echo $stats['total_annonces']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>Ventes</h3>
                <p><?php echo $stats['total_ventes']; ?></p>
                <small>
                    <?php echo number_format($stats['chiffre_affaires'], 0, ',', ' '); ?> FCFA
                </small>
            </div>
        </div>
    </div>
    
    <div class="admin-grid">
        <div class="admin-card">
            <h2>Dernières Inscriptions</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $recent_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></td>
                                <td>
                                    <a href="voir_utilisateur.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-small">Voir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="admin-card">
            <h2>Dernières Ventes</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bétail</th>
                            <th>Vendeur</th>
                            <th>Acheteur</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($sale['date_commande'])); ?></td>
                                <td><?php echo htmlspecialchars($sale['nom_betail']); ?></td>
                                <td><?php echo htmlspecialchars($sale['vendeur_nom']); ?></td>
                                <td><?php echo htmlspecialchars($sale['acheteur_nom']); ?></td>
                                <td><?php echo number_format($sale['montant'], 0, ',', ' '); ?> FCFA</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.admin-dashboard {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.admin-actions {
    display: flex;
    gap: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
    gap: 1.5rem;
}

.stat-icon {
    font-size: 2.5rem;
}

.stat-content small {
    color: #6b7280;
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
    gap: 1.5rem;
}

.admin-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

th {
    background: #f8fafc;
    font-weight: 600;
}

.btn-small {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .admin-actions {
        width: 100%;
    }
    
    .admin-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$conn->close();
require_once '../includes/footer.php';
?>
