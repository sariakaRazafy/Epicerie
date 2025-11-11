<?php
include('./db/connexion.php');
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

// Récupère le nom du fichier image pour suppression
$res = $cnx->query('SELECT image FROM produits WHERE id=' . $id);
$image = null;
if ($res && $row = $res->fetch_assoc()) {
    $image = $row['image'];
}

// Supprime l'enregistrement
$stmt = $cnx->prepare('DELETE FROM produits WHERE id=?');
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $cnx->error]);
    exit;
}
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    // Supprime le fichier image si présent
    if ($image && file_exists('uploads/' . $image)) {
        @unlink('uploads/' . $image);
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
