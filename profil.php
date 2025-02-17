<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = clean($conn, $_POST['nom']);
    $email = clean($conn, $_POST['email']);
    $telephone = clean($conn, $_POST['telephone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Vérifier si l'email est déjà utilisé par un autre utilisateur
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $error_message = "Cet email est déjà utilisé par un autre utilisateur";
    } else {
        // Si un nouveau mot de passe est fourni
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $error_message = "Les nouveaux mots de passe ne correspondent pas";
            } else {
                // Vérifier l'ancien mot de passe
                $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                
                if (!password_verify($current_password, $user['password_hash'])) {
                    $error_message = "Mot de passe actuel incorrect";
                } else {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET nom = ?, email = ?, telephone = ?, password_hash = ? 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ssssi", $nom, $email, $telephone, $password_hash, $user_id);
                }
            }
        } else {
            // Mise à jour sans changement de mot de passe
            $stmt = $conn->prepare("
                UPDATE users 
                SET nom = ?, email = ?, telephone = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("sssi", $nom, $email, $telephone, $user_id);
        }
        
        if (empty($error_message)) {
            if ($stmt->execute()) {
                $_SESSION['nom'] = $nom;
                $success_message = "Profil mis à jour avec succès";
            } else {
                $error_message = "Erreur lors de la mise à jour du profil";
            }
        }
    }
}

// Récupérer les informations actuelles de l'utilisateur
$stmt = $conn->prepare("SELECT nom, email, telephone, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<div class="profile-container">
    <div class="profile-header">
        <h1>Mon Profil</h1>
        <a href="<?php echo $user['role'] === 'vendeur' ? 'dashboard_vendeur.php' : 'dashboard_acheteur.php'; ?>" 
           class="btn btn-secondary">
            <span class="icon">←</span> Retour au tableau de bord
        </a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="profile-card">
        <form method="POST" class="profile-form">
            <div class="form-section">
                <h2>Informations Personnelles</h2>
                
                <div class="form-group">
                    <label for="nom">Nom complet</label>
                    <input type="text" id="nom" name="nom" required
                           value="<?php echo htmlspecialchars($user['nom']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" required
                           value="<?php echo htmlspecialchars($user['telephone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="role">Rôle</label>
                    <input type="text" id="role" value="<?php echo ucfirst($user['role']); ?>" 
                           readonly class="readonly">
                </div>
            </div>

            <div class="form-section">
                <h2>Changer le mot de passe</h2>
                <p class="section-info">Laissez vide si vous ne souhaitez pas changer de mot de passe</p>
                
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password">
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" 
                           minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           minlength="6">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="icon">💾</span> Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.profile-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.profile-header h1 {
    font-size: 2rem;
    color: var(--text-color);
}

.profile-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--card-shadow);
}

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 2rem;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h2 {
    font-size: 1.5rem;
    color: var(--text-color);
    margin-bottom: 1.5rem;
}

.section-info {
    color: var(--text-light);
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-color);
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition-base);
}

.form-group input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px var(--primary-light);
    outline: none;
}

.form-group input.readonly {
    background-color: var(--background-color);
    cursor: not-allowed;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 1rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.alert.success {
    background-color: var(--success-light);
    color: var(--success-color);
}

.alert.error {
    background-color: var(--error-light);
    color: var(--error-color);
}

@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .profile-card {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<?php
$conn->close();
require_once 'includes/footer.php';
?> 