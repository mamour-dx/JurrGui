<?php
require_once 'includes/header.php';

// Vérification que l'utilisateur est connecté et est un vendeur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendeur') {
    header('Location: connexion.php');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    // Récupération et nettoyage des données
    $nom_betail = clean($conn, $_POST['nom_betail']);
    $categorie = clean($conn, $_POST['categorie']);
    $prix = floatval($_POST['prix']);
    $description = clean($conn, $_POST['description']);
    
    // Validation
    if (empty($nom_betail)) $errors[] = "Le nom du bétail est requis";
    if (!in_array($categorie, ['bovins', 'ovins', 'caprins'])) $errors[] = "Catégorie invalide";
    if ($prix <= 0) $errors[] = "Le prix doit être supérieur à 0";
    
    // Gestion de l'upload de photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['photo']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Vérification du type de fichier
        if (!in_array($filetype, $allowed)) {
            $errors[] = "Format de fichier non autorisé. Utilisez JPG, JPEG ou PNG";
        }
        
        // Vérification de la taille (5MB max)
        if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $errors[] = "L'image ne doit pas dépasser 5MB";
        }
        
        if (empty($errors)) {
            $newname = uniqid() . "." . $filetype;
            $upload_dir = __DIR__ . "/uploads/betail/";
            
            // Création du dossier si nécessaire
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $errors[] = "Impossible de créer le dossier d'upload";
                }
            }
            
            // Vérification des permissions
            if (!is_writable($upload_dir)) {
                $errors[] = "Le dossier d'upload n'a pas les permissions nécessaires";
            }
            
            if (empty($errors)) {
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $newname)) {
                    $photo_path = "uploads/betail/" . $newname;
                } else {
                    $errors[] = "Erreur lors de l'upload de l'image. Code d'erreur : " . $_FILES['photo']['error'];
                }
            }
        }
    } else {
        $errors[] = "Une photo est requise";
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== 0) {
            $errors[] = "Erreur lors de l'upload : " . $_FILES['photo']['error'];
        }
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO betail (vendeur_id, categorie, nom_betail, description, prix, photo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssds", $_SESSION['user_id'], $categorie, $nom_betail, $description, $prix, $photo_path);
        
        if ($stmt->execute()) {
            $success = true;
            header("refresh:2;url=dashboard_vendeur.php");
        } else {
            $errors[] = "Erreur lors de l'ajout de l'annonce";
        }
    }
    
    $conn->close();
}
?>

<div class="container">
    <h2>Ajouter une annonce</h2>
    
    <?php if ($success): ?>
        <div class="alert success">
            Annonce ajoutée avec succès ! Redirection...
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="form-betail">
        <div class="form-group">
            <label for="nom_betail">Nom du bétail</label>
            <input type="text" id="nom_betail" name="nom_betail" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="categorie">Catégorie</label>
            <select id="categorie" name="categorie" class="form-control" required>
                <option value="bovins">Bovins</option>
                <option value="ovins">Ovins</option>
                <option value="caprins">Caprins</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="prix">Prix (FCFA)</label>
            <input type="number" id="prix" name="prix" class="form-control" min="0" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="5" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="photo">Photo</label>
            <input type="file" id="photo" name="photo" class="form-control" accept="image/*" required>
            <div class="preview-image"></div>
        </div>
        
        <button type="submit" class="btn btn-primary">Publier l'annonce</button>
    </form>
</div>

<script>
// Preview de l'image avant upload
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.querySelector('.preview-image');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 200px;">`;
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
