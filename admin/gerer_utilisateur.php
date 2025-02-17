<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

$conn = connectDB();

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtres
$search = isset($_GET['search']) ? clean($conn, $_GET['search']) : '';
$role = isset($_GET['role']) ? clean($conn, $_GET['role']) : '';
$status = isset($_GET['status']) ? clean($conn, $_GET['status']) : '';

// Construction de la requête
$where = ["role != 'admin'"];
if ($search) {
    $where[] = "(nom LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($role) {
    $where[] = "role = '$role'";
}
if ($status !== '') {
    $where[] = "actif = " . ($status === 'actif' ? '1' : '0');
}

$where_clause = implode(' AND ', $where);

// Récupération du nombre total d'utilisateurs
$total = $conn->query("SELECT COUNT(*) as count FROM users WHERE $where_clause")->fetch_assoc()['count'];
$total_pages = ceil($total / $limit);

// Récupération des utilisateurs
$users = $conn->query("
    SELECT id, nom, email, role, date_creation, actif,
           (SELECT COUNT(*) FROM betail WHERE vendeur_id = users.id) as nb_annonces,
           (SELECT COUNT(*) FROM commandes c 
            JOIN betail b ON c.betail_id = b.id 
            WHERE b.vendeur_id = users.id AND c.statut = 'paye') as nb_ventes
    FROM users 
    WHERE $where_clause
    ORDER BY date_creation DESC
    LIMIT $offset, $limit
");
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Gestion des Utilisateurs</h1>
        <a href="index.php" class="btn btn-secondary">Retour au Dashboard</a>
    </div>
    
    <div class="filters">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <input type="text" name="search" placeholder="Rechercher..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group">
                <select name="role">
                    <option value="">Tous les rôles</option>
                    <option value="vendeur" <?php echo $role === 'vendeur' ? 'selected' : ''; ?>>
                        Vendeurs
                    </option>
                    <option value="acheteur" <?php echo $role === 'acheteur' ? 'selected' : ''; ?>>
                        Acheteurs
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <select name="status">
                    <option value="">Tous les statuts</option>
                    <option value="actif" <?php echo $status === 'actif' ? 'selected' : ''; ?>>
                        Actifs
                    </option>
                    <option value="inactif" <?php echo $status === 'inactif' ? 'selected' : ''; ?>>
                        Bloqués
                    </option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </form>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Date d'inscription</th>
                    <th>Annonces</th>
                    <th>Ventes</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['nom']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></td>
                        <td><?php echo $user['nb_annonces']; ?></td>
                        <td><?php echo $user['nb_ventes']; ?></td>
                        <td>
                            <span class="status-badge <?php echo $user['actif'] ? 'active' : 'blocked'; ?>">
                                <?php echo $user['actif'] ? 'Actif' : 'Bloqué'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="voir_utilisateur.php?id=<?php echo $user['id']; ?>" 
                                   class="btn btn-small">Voir</a>
                                   
                                <?php if ($user['actif']): ?>
                                    <button onclick="bloquerUtilisateur(<?php echo $user['id']; ?>)" 
                                            class="btn btn-small btn-danger">Bloquer</button>
                                <?php else: ?>
                                    <button onclick="debloquerUtilisateur(<?php echo $user['id']; ?>)" 
                                            class="btn btn-small btn-success">Débloquer</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                   class="page-link <?php echo $page === $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function bloquerUtilisateur(userId) {
    if (confirm('Êtes-vous sûr de vouloir bloquer cet utilisateur ?')) {
        fetch('../api/admin/bloquer_utilisateur.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors du blocage de l\'utilisateur');
            }
        });
    }
}

function debloquerUtilisateur(userId) {
    if (confirm('Êtes-vous sûr de vouloir débloquer cet utilisateur ?')) {
        fetch('../api/admin/debloquer_utilisateur.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors du déblocage de l\'utilisateur');
            }
        });
    }
}
</script>

<style>
.admin-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.filters {
    margin-bottom: 2rem;
}

.filter-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.875rem;
}

.status-badge.active {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.blocked {
    background: #fee2e2;
    color: #dc2626;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-link {
    padding: 0.5rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    text-decoration: none;
    color: var(--text-color);
}

.page-link.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<?php
$conn->close();
require_once '../includes/footer.php';
?>
