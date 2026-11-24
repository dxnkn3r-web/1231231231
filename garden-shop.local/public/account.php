<?php
require_once __DIR__ . '/includes/functions.php';
$user = currentUser();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<h1>👤 Личный кабинет</h1>
<p style="margin-bottom:20px;">Здравствуйте, <b><?= e($user['full_name'] ?: $user['email']) ?></b>!</p>

<h2>📦 Мои заказы</h2>

<?php if (empty($orders)): ?>
    <p>У вас пока нет заказов. <a href="/index.php">Перейти в каталог</a></p>
<?php else: ?>
    <?php foreach ($orders as $o): ?>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$o['id']]);
        $items = $stmt->fetchAll();
        ?>
        <div class="card" style="padding:20px; margin-bottom:15px;">
            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <b>Заказ #<?= $o['id'] ?></b>
                <span style="color:#2e7d32; font-weight:bold;">
                    <?= number_format($o['total_amount'], 0, '.', ' ') ?> ₽
                </span>
                <span style="color:#666;"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></span>
                <span style="padding:3px 10px; background:#d4edda; border-radius:5px; font-size:0.85rem;">
                    <?= $o['status'] === 'paid' ? '✅ Оплачен' : '⏳ ' . e($o['status']) ?>
                </span>
            </div>
            <ul style="margin-top:10px; padding-left:20px;">
                <?php foreach ($items as $i): ?>
                    <li><?= e($i['product_name']) ?> — <?= $i['quantity'] ?> шт × <?= $i['price_at_moment'] ?> ₽</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>