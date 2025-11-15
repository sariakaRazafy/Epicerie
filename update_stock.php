<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db/connexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$stock = isset($_POST['stock']) ? $_POST['stock'] : null;

if ($id <= 0 || $stock === null) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

// Normalize stock to float (support comma decimal)
$stock = str_replace(',', '.', trim($stock));
if (!is_numeric($stock) || $stock < 0) {
    echo json_encode(['success' => false, 'error' => 'Valeur de stock invalide']);
    exit;
}

$stock = (float)$stock;

$stmt = $cnx->prepare('UPDATE produits SET stock = ? WHERE id = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    exit;
}
$stmt->bind_param('di', $stock, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'stock' => $stock]);
} else {
    echo json_encode(['success' => false, 'error' => 'Impossible de mettre à jour le stock']);
}
$stmt->close();
$cnx->close();
