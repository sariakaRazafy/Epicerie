<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db/connexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Supprime les tokens remember-me s'ils existent
$delTokens = $cnx->prepare('DELETE FROM auth_tokens WHERE user_id = ?');
if ($delTokens) {
    $delTokens->bind_param('i', $userId);
    $delTokens->execute();
    $delTokens->close();
}

// Supprime les order_items et orders liés (selon votre schéma, on peut SKIP ou garder l'historique)
// Ici on garde les commandes (historique) mais supprime l'utilisateur

$del = $cnx->prepare('DELETE FROM utilisateurs WHERE id = ?');
if (!$del) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    exit;
}
$del->bind_param('i', $userId);
if ($del->execute()) {
    // détruit la session
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Impossible de supprimer le compte']);
}

$del->close();
$cnx->close();
