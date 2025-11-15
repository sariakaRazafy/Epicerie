<?php
// Retourne les produits les plus vendus (par revenue) entre deux dates.
// Usage: api/stats/top_sold.php?start=2025-01-01&end=2025-01-31&limit=10
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db/connexion.php';

// Lecture et validation des paramètres
$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Validation simple des dates (YYYY-MM-DD)
function is_valid_date($d)
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

if (!$start || !$end) {
    // par défaut : les 30 derniers jours
    $end = date('Y-m-d');
    $start = date('Y-m-d', strtotime('-30 days'));
}

if (!is_valid_date($start) || !is_valid_date($end)) {
    echo json_encode(['success' => false, 'error' => 'Paramètres de date invalides. Format attendu : YYYY-MM-DD']);
    exit;
}

// LIMIT et OFFSET sont sécurisés car castés en int
$limit = max(1, min(1000, $limit));
$offset = max(0, $offset);

// Requête préparée (les '?' sont valides ici car on utilise mysqli prepared statements)
$sql = "SELECT oi.product_id, oi.product_name, SUM(oi.line_total) AS revenue, SUM(oi.quantity) AS total_qty
FROM order_items oi
JOIN orders o ON o.id = oi.order_id
WHERE DATE(o.created_at) BETWEEN ? AND ?
GROUP BY oi.product_id, oi.product_name
ORDER BY revenue DESC
LIMIT $limit OFFSET $offset";

$stmt = $cnx->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erreur préparation requête: ' . $cnx->error]);
    exit;
}

$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}

echo json_encode(['success' => true, 'start' => $start, 'end' => $end, 'limit' => $limit, 'offset' => $offset, 'data' => $rows]);

$stmt->close();
$cnx->close();
