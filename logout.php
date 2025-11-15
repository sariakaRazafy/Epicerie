<?php
session_start();
// Supprime aussi le cookie "remember" et le token en base si possible
if (isset($_COOKIE['remember'])) {
    $parts = explode(':', $_COOKIE['remember']);
    if (count($parts) === 2) {
        $selector = $parts[0];
        $cnxPath = __DIR__ . '/db/connexion.php';
        if (file_exists($cnxPath)) {
            include $cnxPath;
            // tenter de supprimer l'entrée correspondante
            $del = $cnx->prepare('DELETE FROM auth_tokens WHERE selector = ?');
            if ($del) {
                $del->bind_param('s', $selector);
                $del->execute();
            }
        }
    }
    setcookie('remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// Détruit la session
$_SESSION = [];
session_destroy();

// Redirection vers la page d'accueil
header('Location: index.php');
exit;
