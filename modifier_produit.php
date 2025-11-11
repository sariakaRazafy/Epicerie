<?php
include('./db/connexion.php');
header('Content-Type: application/json');

// Vérifie l'ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

// Récupère les champs
$nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
$prix = isset($_POST['prix']) ? floatval($_POST['prix']) : 0;
$unite = isset($_POST['unite']) ? $_POST['unite'] : 'unité';
$stock_raw = isset($_POST['stock']) ? $_POST['stock'] : '0';

if ($nom === '' || $prix <= 0) {
    echo json_encode(['success' => false, 'error' => 'Nom ou prix manquant']);
    exit;
}

// Validation du stock
if (!is_numeric($stock_raw)) {
    echo json_encode(['success' => false, 'error' => 'Stock invalide']);
    exit;
}
$stock = floatval($stock_raw);
if ($stock < 0) {
    echo json_encode(['success' => false, 'error' => 'Stock doit être positif ou nul']);
    exit;
}
if ($unite === 'unité' && floor($stock) != $stock) {
    echo json_encode(['success' => false, 'error' => "Pour l'unité, la quantité doit être un entier"]);
    exit;
}

// Récupère l'image actuelle
$existingImage = null;
$res = $cnx->query('SELECT image FROM produits WHERE id=' . $id);
if ($res && $row = $res->fetch_assoc()) {
    $existingImage = $row['image'];
}

$target_dir = 'uploads/';
$newImage = $existingImage;

// Si nouvelle image fournie
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file_name = time() . '_' . basename($_FILES['image']['name']);
    $target_file = $target_dir . $file_name;
    $check = getimagesize($_FILES['image']['tmp_name']);
    if ($check === false) {
        echo json_encode(['success' => false, 'error' => 'Fichier non valide']);
        exit;
    }
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        echo json_encode(['success' => false, 'error' => 'Échec upload']);
        exit;
    }
    $newImage = $file_name;
    // supprime l'ancienne image si existe
    if ($existingImage && file_exists($target_dir . $existingImage)) {
        @unlink($target_dir . $existingImage);
    }
}

// Mettre à jour la base
$stmt = $cnx->prepare('UPDATE produits SET nom=?, prix=?, image=?, unite=?, stock=? WHERE id=?');
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $cnx->error]);
    exit;
}
$stmt->bind_param('sdssdi', $nom, $prix, $newImage, $unite, $stock, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
