<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>JurrGui System Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
</style>";

// 1. PHP Version Check
echo "<div class='section'>";
echo "<h2>1. PHP Version Check</h2>";
$php_version = phpversion();
echo "PHP Version: " . $php_version;
if (version_compare($php_version, '7.0.0', '>=')) {
    echo " <span class='success'>[OK]</span>";
} else {
    echo " <span class='error'>[ERROR: PHP 7.0.0 or higher required]</span>";
}
echo "</div>";

// 2. Required Extensions
echo "<div class='section'>";
echo "<h2>2. Required Extensions</h2>";
$required_extensions = ['mysqli', 'json', 'session', 'gd'];
foreach ($required_extensions as $ext) {
    echo "Extension $ext: ";
    if (extension_loaded($ext)) {
        echo "<span class='success'>[OK]</span><br>";
    } else {
        echo "<span class='error'>[MISSING]</span><br>";
    }
}
echo "</div>";

// 3. Directory Structure
echo "<div class='section'>";
echo "<h2>3. Directory Structure</h2>";
$required_dirs = [
    'includes',
    'assets',
    'assets/images',
    'api',
    'sql'
];
foreach ($required_dirs as $dir) {
    echo "Directory /$dir: ";
    if (is_dir($dir)) {
        echo "<span class='success'>[EXISTS]</span> ";
        // Check permissions
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "Permissions: $perms";
        if ($perms >= "0755") {
            echo " <span class='success'>[OK]</span>";
        } else {
            echo " <span class='error'>[INSUFFICIENT PERMISSIONS]</span>";
        }
        echo "<br>";
    } else {
        echo "<span class='error'>[MISSING]</span><br>";
    }
}
echo "</div>";

// 4. Required Files
echo "<div class='section'>";
echo "<h2>4. Required Files</h2>";
$required_files = [
    'includes/header.php',
    'includes/footer.php',
    'includes/db.php',
    'index.php',
    'connexion.php',
    'inscription.php',
    'profil.php',
    'mes_commandes.php'
];
foreach ($required_files as $file) {
    echo "File /$file: ";
    if (file_exists($file)) {
        echo "<span class='success'>[EXISTS]</span> ";
        // Check if file is readable
        if (is_readable($file)) {
            echo "<span class='success'>[READABLE]</span>";
        } else {
            echo "<span class='error'>[NOT READABLE]</span>";
        }
        echo "<br>";
    } else {
        echo "<span class='error'>[MISSING]</span><br>";
    }
}
echo "</div>";

// 5. Database Connection
echo "<div class='section'>";
echo "<h2>5. Database Connection</h2>";
if (file_exists('includes/db.php')) {
    require_once 'includes/db.php';
    try {
        $conn = connectDB();
        echo "Database connection: <span class='success'>[OK]</span><br>";
        
        // Check if required tables exist
        $required_tables = ['users', 'betail', 'commandes', 'avis', 'panier'];
        foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            echo "Table $table: ";
            if ($result->num_rows > 0) {
                echo "<span class='success'>[EXISTS]</span><br>";
            } else {
                echo "<span class='error'>[MISSING]</span><br>";
            }
        }
        
        $conn->close();
    } catch (Exception $e) {
        echo "Database connection: <span class='error'>[FAILED: " . $e->getMessage() . "]</span><br>";
    }
} else {
    echo "Database configuration file: <span class='error'>[MISSING]</span><br>";
}
echo "</div>";

// 6. Server Information
echo "<div class='section'>";
echo "<h2>6. Server Information</h2>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Path: " . __FILE__ . "<br>";
echo "Base URL: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]<br>";
echo "</div>";

// 7. Session Check
echo "<div class='section'>";
echo "<h2>7. Session Check</h2>";
if (session_start()) {
    echo "Session start: <span class='success'>[OK]</span><br>";
    echo "Session save path: " . session_save_path() . "<br>";
    if (is_writable(session_save_path())) {
        echo "Session directory writable: <span class='success'>[OK]</span><br>";
    } else {
        echo "Session directory writable: <span class='error'>[ERROR]</span><br>";
    }
} else {
    echo "Session start: <span class='error'>[FAILED]</span><br>";
}
echo "</div>";

// 8. Image Upload Directory
echo "<div class='section'>";
echo "<h2>8. Image Upload Directory</h2>";
$upload_dir = 'assets/images';
if (is_dir($upload_dir)) {
    echo "Upload directory exists: <span class='success'>[OK]</span><br>";
    if (is_writable($upload_dir)) {
        echo "Upload directory writable: <span class='success'>[OK]</span><br>";
    } else {
        echo "Upload directory writable: <span class='error'>[ERROR]</span><br>";
    }
} else {
    echo "Upload directory: <span class='error'>[MISSING]</span><br>";
}
echo "</div>"; 