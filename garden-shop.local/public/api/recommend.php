<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

$light    = $input['light']    ?? '';
$soil     = $input['soil']     ?? '';
$moisture = $input['moisture'] ?? '';
$frost    = $input['frost']    ?? '';

$sql = "SELECT *,
    (
        CASE WHEN light_level = :light THEN 25 ELSE 0 END +
        CASE WHEN soil_type = :soil THEN 25 ELSE 0 END +
        CASE WHEN moisture = :moisture THEN 25 ELSE 0 END +
        CASE WHEN frost_resistance = :frost THEN 25 ELSE 0 END
    ) AS match_score
    FROM products
    WHERE stock_quantity > 0
    ORDER BY match_score DESC, price ASC
    LIMIT 6";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':light'    => $light,
    ':soil'     => $soil,
    ':moisture' => $moisture,
    ':frost'    => $frost,
]);

$products = $stmt->fetchAll();
$products = array_filter($products, fn($p) => $p['match_score'] > 0);

echo json_encode(array_values($products), JSON_UNESCAPED_UNICODE);