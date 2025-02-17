<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/header.php';
require_once 'includes/google_config.php'; // À créer pour la configuration OAuth

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    $email = clean($conn, $_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $errors[] = "Veuillez remplir tous les champs";
    } else {
        $stmt = $conn->prepare("SELECT id, nom, email, password_hash, role FROM users WHERE email = ? AND actif = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                // Création de la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirection selon le rôle
                $redirect = 'index.php';
                if ($user['role'] === 'vendeur') {
                    $redirect = 'dashboard_vendeur.php';
                } elseif ($user['role'] === 'acheteur') {
                    $redirect = 'dashboard_acheteur.php';
                }
                
                header("Location: $redirect");
                exit();
            } else {
                $errors[] = "Email ou mot de passe incorrect";
            }
        } else {
            $errors[] = "Email ou mot de passe incorrect";
        }
    }
    
    $conn->close();
}
?>

<div class="auth-container">
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <h2>Connexion</h2>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Se connecter</button>
        
        <div class="google-auth">
            <a href="<?php echo $googleAuthUrl; ?>" class="btn btn-google">
                <img src="assets/images/google-icon.png" alt="Google">
                Se connecter avec Google
            </a>
        </div>
        
        <div class="auth-links">
            <p>Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>
            <p><a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a></p>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>