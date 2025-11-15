<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db/connexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$current = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
$new = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
$confirm = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

if ($current === '' || $new === '' || $confirm === '') {
    echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis']);
    exit;
}
if ($new !== $confirm) {
    echo json_encode(['success' => false, 'error' => 'Les nouveaux mots de passe ne correspondent pas']);
    exit;
}
if (strlen($new) < 5) {
    echo json_encode(['success' => false, 'error' => 'Mot de passe trop court']);
    exit;
}

$stmt = $cnx->prepare('SELECT password FROM utilisateurs WHERE id = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    exit;
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
    exit;
}
$row = $res->fetch_assoc();
if (!password_verify($current, $row['password'])) {
    echo json_encode(['success' => false, 'error' => 'Mot de passe actuel incorrect']);
    exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$upd = $cnx->prepare('UPDATE utilisateurs SET password = ? WHERE id = ?');
$upd->bind_param('si', $hash, $userId);
if ($upd->execute()) echo json_encode(['success' => true]);
else echo json_encode(['success' => false, 'error' => 'Impossible de mettre à jour']);

$stmt->close();
$upd->close();
$cnx->close();
