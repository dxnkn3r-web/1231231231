<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Получаем ID и остатки всех товаров
$stmt = $pdo->query("SELECT id, stock_quantity FROM products");
$stocks = [];
while($row = $stmt->fetch()) {
    $stocks[$row['id']] = $row['stock_quantity'];
}

echo json_encode($stocks);