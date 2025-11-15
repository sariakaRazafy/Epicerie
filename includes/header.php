<?php
// Démarre la session pour vérifier l'authentification
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si l'utilisateur n'a pas de session mais possède un cookie 'remember', tenter
// une reconnexion automatique via le token selector:validator
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember'])) {
    // inclusion conditionnelle de la connexion DB
    $cnxPath = __DIR__ . '/../db/connexion.php';
    if (file_exists($cnxPath)) {
        include $cnxPath;
        try {
            $parts = explode(':', $_COOKIE['remember']);
            if (count($parts) === 2) {
                $selector = $cnx->real_escape_string($parts[0]);
                $validator = $parts[1];
                $stmt = $cnx->prepare('SELECT id, user_id, hashed_validator, expires FROM auth_tokens WHERE selector = ? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('s', $selector);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows === 1) {
                        $row = $res->fetch_assoc();
                        // vérifier expiration
                        if (strtotime($row['expires']) > time() && password_verify($validator, $row['hashed_validator'])) {
                            // récupère les infos utilisateur
                            $u = $cnx->prepare('SELECT id, username FROM utilisateurs WHERE id = ? LIMIT 1');
                            if ($u) {
                                $u->bind_param('i', $row['user_id']);
                                $u->execute();
                                $ur = $u->get_result();
                                if ($ur && $ur->num_rows === 1) {
                                    $user = $ur->fetch_assoc();
                                    $_SESSION['user_id'] = $user['id'];
                                    $_SESSION['user_name'] = $user['username'];
                                    // (optionnel) ici on pourrait rotaer/renouveler le token pour éviter replay attacks
                                }
                            }
                        } else {
                            // token invalide ou expiré => suppression éventuelle
                            $del = $cnx->prepare('DELETE FROM auth_tokens WHERE selector = ?');
                            if ($del) {
                                $del->bind_param('s', $selector);
                                $del->execute();
                            }
                            setcookie('remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // si quelque chose casse, on ne bloque pas la page
        }
    }
}

// Fonction pour prot�ger une page (redirection si pas authentifi�)
function protegerPage()
{
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
        <a class="navbar-brand me-4" href="index.php">Epicerie</a>

        <div class="navbar-nav ms-auto">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Affiche les liens si authentifié -->
                <a class="nav-link mx-2" href="produits.php">Produits</a>
                <a class="nav-link mx-2" href="statistiques.php">Statistiques</a>
                <a class="nav-link mx-2" href="inventaire.php">Inventaire</a>
                <a class="nav-link mx-2" href="compte.php">Mon compte</a>
                <span class="nav-link mx-2 text-muted">Connecté: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a class="nav-link mx-2" href="logout.php">Déconnexion</a>
            <?php else: ?>
                <!-- Affiche un lien d'accueil si pas authentifié -->
                <a class="nav-link mx-2" href="index.php">Accueil</a>
            <?php endif; ?>
        </div>
    </nav>