<?php
session_start();
require_once 'includes/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    // Récupération et nettoyage des données
    $nom = clean($conn, $_POST['nom']);
    $email = clean($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = clean($conn, $_POST['role']);
    $telephone = clean($conn, $_POST['telephone']);
    
    // Validation simple
    if (empty($nom) || empty($email) || empty($password) || empty($role) || empty($telephone)) {
        $errors[] = "Tous les champs sont requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    } elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit faire au moins 6 caractères";
    }
    
    // Vérification email unique
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Cet email est déjà utilisé";
        }
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (nom, email, password_hash, role, telephone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nom, $email, $password_hash, $role, $telephone);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['nom'] = $nom;
            $_SESSION['role'] = $role;
            
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Erreur lors de l'inscription";
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - JurrGui</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="auth-container">
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
                <input type="text" id="nom" name="nom" required 
                       value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" required
                       value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="role">Je suis un</label>
                <select id="role" name="role" required>
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
</body>
</html>