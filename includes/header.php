<?php
// Démarre la session pour vérifier l'authentification
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour protéger une page (redirection si pas authentifié)
function protegerPage() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Veuillez vous connecter pour accéder à cette page.';
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Epicerie</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <?php
    // Use a relative stylesheet path and add a cache-busting version based on file modification time
    $cssPath = __DIR__ . '/../assets/css/style.css';
    $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
    ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo $cssVersion; ?>">
</head>

<body>

    <nav class="navbar navbar-expand-lg bg-dark navbar-dark px-3">
        <a class="navbar-brand me-4" href="index.php">Épicerie</a>

        <div class="navbar-nav ms-auto">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Affiche les liens si authentifié -->
                <a class="nav-link mx-2" href="produits.php">Produits</a>
                <a class="nav-link mx-2" href="statistiques.php">Statistiques</a>
                <a class="nav-link mx-2" href="compte.php">Mon compte</a>
                <span class="nav-link mx-2 text-muted">Connecté: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a class="nav-link mx-2" href="logout.php">Déconnexion</a>
            <?php else: ?>
                <!-- Affiche un lien d'accueil si pas authentifié -->
                <a class="nav-link mx-2" href="index.php">Accueil</a>
            <?php endif; ?>
        </div>
    </nav>
