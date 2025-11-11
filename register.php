<?php
session_start();
include('./db/connexion.php');
header('Content-Type: application/json');

// Récupération et validation des champs
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    echo json_encode(['success' => false, 'error' => 'Tous les champs sont obligatoires.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Email invalide.']);
    exit;
}
if ($password !== $confirm) {
    echo json_encode(['success' => false, 'error' => 'Les mots de passe ne correspondent pas.']);
    exit;
}
if (strlen($password) < 5) {
    echo json_encode(['success' => false, 'error' => 'Mot de passe trop court.']);
    exit;
}

// Vérifie si l'utilisateur existe déjà
$stmt = $cnx->prepare('SELECT id FROM utilisateurs WHERE username=? OR email=?');
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur ou email déjà utilisé.']);
    exit;
}
$stmt->close();

// Hash du mot de passe
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $cnx->prepare('INSERT INTO utilisateurs (username, email, password) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $username, $email, $hash);
if ($stmt->execute()) {
    $_SESSION['user_id'] = $stmt->insert_id;
    $_SESSION['user_name'] = $username;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du compte.']);
}
