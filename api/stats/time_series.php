<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db/connexion.php';

$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;

function is_valid_date($d)
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

if (!$start || !$end) {
    $end = date('Y-m-d');
    $start = date('Y-m-d', strtotime('-30 days'));
}
if (!is_valid_date($start) || !is_valid_date($end)) {
    echo json_encode(['success' => false, 'error' => 'Paramètres de date invalides (YYYY-MM-DD)']);
    exit;
}

$sql = "SELECT DATE(o.created_at) AS day, SUM(oi.quantity) AS qty_sold, SUM(oi.line_total) AS revenue
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE DATE(o.created_at) BETWEEN ? AND ?
GROUP BY day
ORDER BY day ASC";

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

echo json_encode(['success' => true, 'start' => $start, 'end' => $end, 'data' => $rows]);

$stmt->close();
$cnx->close();
