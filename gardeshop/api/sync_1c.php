<?php
/**
 * Эмуляция синхронизации с 1С.
 * Открыть: http://garden-shop.local/api/sync_1c.php
 */
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$stockData = [
    ['sku' => 'ART-001', 'quantity' => rand(5, 50),  'price' => 1500],
    ['sku' => 'ART-002', 'quantity' => rand(0, 30),  'price' => 800],
    ['sku' => 'ART-003', 'quantity' => rand(10, 100),'price' => 1200],
    ['sku' => 'ART-004', 'quantity' => rand(0, 20),  'price' => 2500],
    ['sku' => 'ART-005', 'quantity' => rand(5, 15),  'price' => 1800],
    ['sku' => 'ART-006', 'quantity' => rand(3, 20),  'price' => 1600],
    ['sku' => 'ART-007', 'quantity' => rand(10, 50), 'price' => 950],
];

$updated = 0;

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, price = ? WHERE sku = ?");
    foreach ($stockData as $item) {
        $stmt->execute([$item['quantity'], $item['price'], $item['sku']]);
        $updated += $stmt->rowCount();
    }

    $pdo->prepare("INSERT INTO sync_log (products_updated, status) VALUES (?, 'success')")
        ->execute([$updated]);

    $pdo->commit();

    echo "✅ Синхронизация с 1С завершена\n";
    echo "Обновлено товаров: $updated\n";
    echo "Время: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Ошибка: " . $e->getMessage();
}