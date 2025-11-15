<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db/connexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($username === '' || $email === '') {
    echo json_encode(['success' => false, 'error' => 'Champs requis manquants']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Email invalide']);
    exit;
}

// Vérifie unicité
$stmt = $cnx->prepare('SELECT id FROM utilisateurs WHERE (username = ? OR email = ?) AND id != ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    exit;
}
$stmt->bind_param('ssi', $username, $email, $userId);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Nom d\'utilisateur ou email déjà utilisé']);
    exit;
}
$stmt->close();

$upd = $cnx->prepare('UPDATE utilisateurs SET username = ?, email = ? WHERE id = ?');
if (!$upd) {
    echo json_encode(['success' => false, 'error' => $cnx->error]);
    exit;
}
$upd->bind_param('ssi', $username, $email, $userId);
if ($upd->execute()) {
    $_SESSION['user_name'] = $username;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour']);
}
$upd->close();
$cnx->close();
