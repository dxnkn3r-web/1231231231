<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /index.php');
    exit;
}

// Получаем товар
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die('Товар не найден. <a href="/index.php">Вернуться в каталог</a>');
}

// Получаем похожие товары (двусторонняя связь)
$stmt = $pdo->prepare("
    SELECT DISTINCT p.* FROM products p
    JOIN related_products rp ON (p.id = rp.related_id OR p.id = rp.product_id)
    WHERE (rp.product_id = ? OR rp.related_id = ?) 
    AND p.id != ? 
    AND p.stock_quantity > 0
");
$stmt->execute([$id, $id, $id]);
$related = $stmt->fetchAll();

// Если вручную ничего не задано — автоматический подбор по параметрам
if (empty($related)) {
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE id != ? AND stock_quantity > 0 
        AND (light_level = ? OR soil_type = ?)
        LIMIT 4
    ");
    $stmt->execute([$id, $product['light_level'], $product['soil_type']]);
    $related = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex; gap:30px; flex-wrap:wrap; margin-bottom:40px;">
    <div style="flex:1; min-width:300px;">
        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" style="width:100%; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
    </div>
    <div style="flex:1; min-width:300px;">
        <h1 style="margin-top:0;"><?= e($product['name']) ?></h1>
        <div style="font-size: 2rem; color: #2e7d32; font-weight: bold; margin-bottom: 20px;">
            <?= number_format($product['price'], 0, '.', ' ') ?> ₽ <span style="font-size: 1rem; color: #888; font-weight: normal;">/ шт.</span>
        </div>
        <p style="font-size: 1.1rem; line-height: 1.6; color: #444; margin-bottom: 20px;">
            <?= e($product['description']) ?>
        </p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-top:0; font-size: 1rem; color:#666;">Характеристики:</h3>
            <p>☀️ <b>Освещённость:</b> <?= e($product['light_level']) ?></p>
            <p>🟤 <b>Почва:</b> <?= e($product['soil_type']) ?></p>
            <p>💧 <b>Влажность:</b> <?= e($product['moisture']) ?></p>
            <p>❄️ <b>Морозостойкость:</b> <?= e($product['frost_resistance']) ?></p>
        </div>

        <div style="font-size: 1.1rem; margin-bottom: 15px;">
            <?php if ($product['stock_quantity'] > 0): ?>
                <span style="color:#2e7d32;">● В наличии: <b><?= $product['stock_quantity'] ?></b> шт.</span>
            <?php else: ?>
                <span style="color:#c62828;">● Нет в наличии</span>
            <?php endif; ?>
        </div>

        <?php if ($product['stock_quantity'] > 0): ?>
            <div style="display:flex; align-items:center; gap:15px; margin-bottom: 15px;">
                <label style="font-size: 1rem; margin:0;">Количество:</label>
                <input type="number" id="qty-main-<?= $product['id'] ?>" value="1" min="1" max="<?= $product['stock_quantity'] ?>" 
                       style="width: 80px; padding: 8px; margin:0; text-align: center; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem;">
            </div>

            <button onclick="addToCart(<?= $product['id'] ?>, '<?= e(addslashes($product['name'])) ?>', <?= $product['price'] ?>, document.getElementById('qty-main-<?= $product['id'] ?>').value)" 
                    class="btn-primary" style="font-size: 1.2rem; padding: 15px 30px; width: 100%;">
                🛒 Добавить в корзину
            </button>
        <?php else: ?>
            <button disabled style="background:#ccc; cursor:not-allowed; font-size: 1.2rem; padding: 15px 30px; width: 100%;">
                Нет в наличии
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Блок похожих товаров с якорем #related -->
<?php if (!empty($related)): ?>
    <hr style="border:0; border-top:1px solid #ddd; margin:40px 0;">
    <h2 id="related" style="margin-top:0;">🌱 Похожие растения (или подойдут для тех же условий)</h2>
    <div class="grid">
        <?php foreach ($related as $r): ?>
            <div class="card">
                <!-- Ссылка на карточку товара -->
                <a href="/product.php?id=<?= $r['id'] ?>" style="display: block; overflow: hidden;">
                    <img src="<?= e($r['image_url']) ?>" alt="<?= e($r['name']) ?>" loading="lazy">
                </a>
                
                <div class="card-body">
                    <a href="/product.php?id=<?= $r['id'] ?>" style="text-decoration:none; color:inherit;">
                        <h3><?= e($r['name']) ?></h3>
                    </a>
                    
                    <p><?= e(mb_substr($r['description'], 0, 60)) ?>...</p>
                    
                    <!-- Цена и цена за штуку -->
                    <div style="margin-bottom: 5px; display:flex; align-items: baseline; gap: 5px;">
                        <span class="price" style="font-size: 1.4rem;"><?= number_format($r['price'], 0, '.', ' ') ?> ₽</span>
                        <span style="font-size: 0.85rem; color: #888;">/ шт.</span>
                    </div>
                    
                    <!-- Наличие -->
                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">
                        <?php if ($r['stock_quantity'] > 0): ?>
                            <span style="color:#2e7d32;">● В наличии: <?= $r['stock_quantity'] ?> шт.</span>
                        <?php else: ?>
                            <span style="color:#c62828;">● Нет в наличии</span>
                        <?php endif; ?>
                    </div>

                    <!-- Кнопки управления -->
                    <?php if ($r['stock_quantity'] > 0): ?>
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom: 10px;">
                            <label style="font-size: 0.9rem; margin:0;">Кол-во:</label>
                            <input type="number" id="qty-rel-<?= $r['id'] ?>" value="1" min="1" max="<?= $r['stock_quantity'] ?>" 
                                   style="width: 70px; padding: 5px; margin:0; text-align: center; border: 1px solid #ccc; border-radius: 5px;">
                        </div>
                        
                        <button onclick="addToCart(<?= $r['id'] ?>, '<?= e(addslashes($r['name'])) ?>', <?= $r['price'] ?>, document.getElementById('qty-rel-<?= $r['id'] ?>').value)">
                            В корзину
                        </button>
                    <?php else: ?>
                        <button disabled style="background:#ccc; cursor:not-allowed; margin-bottom:10px; width:100%;">Нет в наличии</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>