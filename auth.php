<?php
session_start();
include("./db/connexion.php");

header('Content-Type: application/json');

// NOTE: pour activer complètement la fonctionnalité "remember me", créez manuellement
// la table `auth_tokens` via phpMyAdmin. Exemple SQL (à exécuter dans phpMyAdmin):
/*
CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `selector` VARCHAR(24) NOT NULL,
  `hashed_validator` VARCHAR(255) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `expires` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`selector`)
);
*/

// Réception du formulaire de login
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$remember = isset($_POST['remember']) && $_POST['remember'] == '1';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Identifiant ou mot de passe manquant']);
    exit;
}

// Cherche l'utilisateur dans la base de données
// On supporte les colonnes `username`/`email`/`password` (utilisé par register.php)
$sql = "SELECT id, username, email, password FROM utilisateurs WHERE username = ? OR email = ? LIMIT 1";
$stmt = $cnx->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    exit;
}
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Identifiant ou mot de passe incorrect']);
    exit;
}

$user = $result->fetch_assoc();

// Vérifie le mot de passe
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'error' => 'Identifiant ou mot de passe incorrect']);
    exit;
}

// Authentification réussie : crée une session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['username'];

// Si l'utilisateur a demandé à être « remembered », crée un token persistant
if ($remember) {
    try {
        // selector (court) + validator (long)
        $selector = bin2hex(random_bytes(9)); // 18 hex chars
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 jours

        // Insert dans la table auth_tokens (si elle existe)
        $ins = $cnx->prepare('INSERT INTO auth_tokens (selector, hashed_validator, user_id, expires) VALUES (?, ?, ?, ?)');
        if ($ins) {
            $ins->bind_param('ssis', $selector, $hashedValidator, $user['id'], $expires);
            $ins->execute();
            $ins->close();

            // Crée le cookie (selector:validator)
            $cookieValue = $selector . ':' . $validator;
            // durée 30 jours, HttpOnly
            setcookie('remember', $cookieValue, time() + 60 * 60 * 24 * 30, '/', '', isset($_SERVER['HTTPS']), true);
        }
    } catch (Exception $e) {
        // En cas d'erreur, on ignore le remember pour ne pas bloquer la connexion
    }
}

echo json_encode(['success' => true, 'message' => 'Connecté avec succès']);
