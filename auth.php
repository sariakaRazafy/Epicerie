<?php
session_start();
include("./db/connexion.php");

header('Content-Type: application/json');

// Réception du formulaire de login
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Identifiant ou mot de passe manquant']);
    exit;
}

// Cherche l'utilisateur dans la base de données
$sql = "SELECT id, nom, mot_de_passe FROM utilisateurs WHERE nom = ?";
$stmt = $cnx->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    exit;
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Identifiant ou mot de passe incorrect']);
    exit;
}

$user = $result->fetch_assoc();

// Vérifie le mot de passe
if (!password_verify($password, $user['mot_de_passe'])) {
    echo json_encode(['success' => false, 'error' => 'Identifiant ou mot de passe incorrect']);
    exit;
}

// Authentification réussie : crée une session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['nom'];

echo json_encode(['success' => true, 'message' => 'Connecté avec succès']);
