<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db/connexion.php';

// Accepte JSON POST { produits: [{id, nom, prix, quantite, unite}], total, payment_method }
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

$produits = isset($data['produits']) ? $data['produits'] : [];
$total = isset($data['total']) ? floatval($data['total']) : 0.0;
$payment_method = isset($data['payment_method']) ? $data['payment_method'] : null;
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (empty($produits) || $total <= 0) {
    echo json_encode(['success' => false, 'error' => 'Panier vide ou total invalide']);
    exit;
}

// Commence une transaction
$cnx->begin_transaction();
try {
    // Insère la commande
    $stmt = $cnx->prepare('INSERT INTO orders (user_id, total_amount, payment_method) VALUES (?, ?, ?)');
    if (!$stmt) throw new Exception('Préparation orders failed: ' . $cnx->error);
    $stmt->bind_param('ids', $user_id, $total, $payment_method);
    if (!$stmt->execute()) throw new Exception('Insertion order failed: ' . $stmt->error);
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Pour chaque produit, vérifie le stock, insère la ligne et met à jour le stock
    foreach ($produits as $p) {
        $prod_id = isset($p['id']) ? intval($p['id']) : null;
        $prod_name = isset($p['nom']) ? $p['nom'] : (isset($p['name']) ? $p['name'] : '');
        $qty = isset($p['quantite']) ? floatval($p['quantite']) : 0;
        $unit = isset($p['unite']) ? $p['unite'] : null;
        $unit_price = isset($p['prix']) ? floatval($p['prix']) : 0.0;
        $line_total = round($qty * $unit_price, 2);

        if (!$prod_id || $qty <= 0) throw new Exception('Produit invalide dans le panier');

        // Verrouille la ligne produit et vérifie stock
        $sel = $cnx->prepare('SELECT stock FROM produits WHERE id = ? FOR UPDATE');
        if (!$sel) throw new Exception('Préparation select produit failed: ' . $cnx->error);
        $sel->bind_param('i', $prod_id);
        $sel->execute();
        $res = $sel->get_result();
        if ($res->num_rows === 0) throw new Exception('Produit introuvable: ' . $prod_id);
        $row = $res->fetch_assoc();
        $current_stock = floatval($row['stock']);
        $sel->close();

        if ($current_stock < $qty) {
            throw new Exception('Stock insuffisant pour le produit ID ' . $prod_id . ' (reste ' . $current_stock . ')');
        }

        // Insert order_item
        $ins = $cnx->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if (!$ins) throw new Exception('Préparation order_items failed: ' . $cnx->error);
        $ins->bind_param('iisdidd', $order_id, $prod_id, $prod_name, $qty, $unit, $unit_price, $line_total);
        if (!$ins->execute()) throw new Exception('Insertion order_item failed: ' . $ins->error);
        $ins->close();

        // Met à jour le stock
        $upd = $cnx->prepare('UPDATE produits SET stock = stock - ? WHERE id = ?');
        if (!$upd) throw new Exception('Préparation update stock failed: ' . $cnx->error);
        $upd->bind_param('di', $qty, $prod_id);
        if (!$upd->execute()) throw new Exception('Mise à jour stock failed: ' . $upd->error);
        $upd->close();
    }

    $cnx->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
    exit;
} catch (Exception $e) {
    $cnx->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
