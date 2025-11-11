<?php
include("./db/connexion.php");

header('Content-Type: application/json');

// Récupère et valide les champs du formulaire (nom, prix, unite, stock, image)
$nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
$prix = isset($_POST['prix']) ? floatval($_POST['prix']) : 0;
$unite = isset($_POST['unite']) ? $_POST['unite'] : 'unité';
$stock_raw = isset($_POST['stock']) ? $_POST['stock'] : '0';

// Validation basique
if ($nom === '' || $prix <= 0) {
    echo json_encode(["success" => false, "error" => "Nom ou prix manquant ou invalide"]);
    exit;
}

// Validation du stock selon l'unité
if (!is_numeric($stock_raw)) {
    echo json_encode(["success" => false, "error" => "Stock invalide"]);
    exit;
}
$stock = floatval($stock_raw);
if ($stock < 0) {
    echo json_encode(["success" => false, "error" => "Stock doit être positif ou nul"]);
    exit;
}
if ($unite === 'unité' && floor($stock) != $stock) {
    echo json_encode(["success" => false, "error" => "Pour l'unité, la quantité doit être un entier"]);
    exit;
}

// Dossier où seront stockées les images
$target_dir = "uploads/";
$file_name = null;

// Si un fichier image a été envoyé, on le traite
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // Génère un nom de fichier unique pour éviter les conflits
    $file_name = time() . "_" . basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $file_name;

    // Vérifie que le fichier uploadé est bien une image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        echo json_encode(["success" => false, "error" => "Fichier non valide"]);
        exit;
    }

    // Déplace l'image uploadée dans le dossier uploads
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        echo json_encode(["success" => false, "error" => "Échec upload"]);
        exit;
    }
}

// Prépare et exécute l'insertion en base (incluant unite et stock si la colonne existe)
$sql = "INSERT INTO produits (nom, prix, image, unite, stock) VALUES (?, ?, ?, ?, ?)";
$stmt = $cnx->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Prepare failed: " . $cnx->error]);
    exit;
}
$stmt->bind_param("sdssi", $nom, $prix, $file_name, $unite, $stock);

if ($stmt->execute()) {
    // Succès : retourne l'ID du nouveau produit et le nom du fichier image
    echo json_encode(["success" => true, "id" => $stmt->insert_id, "image" => $file_name]);
} else {
    // Erreur SQL
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
