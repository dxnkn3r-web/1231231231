<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Необходимо войти в систему для оформления заказа'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$items   = $input['items']   ?? [];
$address = $input['address'] ?? '';

if (empty($items)) {
    echo json_encode(['error' => 'Корзина пуста'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = $_SESSION['user_id'];
$total = 0;

$pdo->beginTransaction();

try {
    // 1. ПРОВЕРКА ОСТАТКОВ И РАСЧЕТ СУММЫ
    foreach ($items as $item) {
        $stmt = $pdo->prepare("SELECT price, stock_quantity, name FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$item['id']]);
        $p = $stmt->fetch();

        if (!$p) {
            throw new Exception("Товар с ID {$item['id']} не найден.");
        }
        
        // Если на складе меньше, чем в корзине — выдаем ошибку
        if ($p['stock_quantity'] < $item['quantity']) {
            throw new Exception("Товара «{$p['name']}» осталось только {$p['stock_quantity']} шт. Пожалуйста, измените количество в корзине.");
        }
        
        $total += $p['price'] * $item['quantity'];
    }

    // 2. СОЗДАНИЕ ЗАКАЗА
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $total, $address]);
    $order_id = $pdo->lastInsertId();

    // 3. ДОБАВЛЕНИЕ ПОЗИЦИЙ И СПИСАНИЕ ОСТАТКОВ
    foreach ($items as $item) {
        $stmt = $pdo->prepare("SELECT price, name FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $p = $stmt->fetch();

        // Сохраняем в историю заказов
        $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price_at_moment) VALUES (?, ?, ?, ?, ?)")
            ->execute([$order_id, $item['id'], $p['name'], $item['quantity'], $p['price']]);

        // УМЕНЬШАЕМ ОСТАТОК НА СКЛАДЕ
        $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")
            ->execute([$item['quantity'], $item['id']]);
    }

    // 4. ЭМУЛЯЦИЯ ОПЛАТЫ (заказ сразу становится оплаченным)
    $pdo->prepare("UPDATE orders SET status = 'paid', payment_id = ? WHERE id = ?")
        ->execute(['DEMO_' . uniqid(), $order_id]);

    $pdo->commit();

    echo json_encode([
        'success'  => true,
        'order_id' => $order_id,
        'total'    => $total
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}