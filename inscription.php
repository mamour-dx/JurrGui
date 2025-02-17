<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    // Récupération et nettoyage des données
    $nom = clean($conn, $_POST['nom']);
    $email = clean($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = clean($conn, $_POST['role']);
    $telephone = clean($conn, $_POST['telephone']);
    
    // Validation
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($email)) $errors[] = "L'email est requis";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format d'email invalide";
    if (strlen($password) < 8) $errors[] = "Le mot de passe doit faire au moins 8 caractères";
    if ($password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas";
    if (!in_array($role, ['vendeur', 'acheteur'])) $errors[] = "Rôle invalide";
    if (empty($telephone)) $errors[] = "Le numéro de téléphone est requis";
    
    // Vérification si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Cet email est déjà utilisé";
    }
    
    if (empty($errors)) {
        // Hash du mot de passe
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertion de l'utilisateur
        $stmt = $conn->prepare("INSERT INTO users (nom, email, password_hash, role, telephone, date_creation, actif) VALUES (?, ?, ?, ?, ?, NOW(), 1)");
        $stmt->bind_param("sssss", $nom, $email, $password_hash, $role, $telephone);
        
        if ($stmt->execute()) {
            $success = true;
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['nom'] = $nom;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            
            // Redirection après 2 secondes
            header("refresh:2;url=index.php");
        } else {
            $errors[] = "Erreur lors de l'inscription: " . $conn->error;
        }
    }
    
    $conn->close();
}
?>

<div class="auth-container">
    <?php if ($success): ?>
        <div class="alert success">
            <p>Inscription réussie ! Vous allez être redirigé vers la page d'accueil...</p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <h2>Inscription</h2>
        
        <div class="form-group">
            <label for="nom">Nom complet</label>
            <input type="text" id="nom" name="nom" class="form-control" required 
                   value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone" class="form-control" required
                   value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" class="form-control" required
                   minlength="8">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmer le mot de passe</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                   minlength="8">
        </div>
        
        <div class="form-group">
            <label for="role">Je suis un</label>
            <select id="role" name="role" class="form-control" required>
                <option value="acheteur" <?php echo (isset($_POST['role']) && $_POST['role'] === 'acheteur') ? 'selected' : ''; ?>>Acheteur</option>
                <option value="vendeur" <?php echo (isset($_POST['role']) && $_POST['role'] === 'vendeur') ? 'selected' : ''; ?>>Vendeur</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">S'inscrire</button>
        
        <div class="auth-links">
            <p>Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>