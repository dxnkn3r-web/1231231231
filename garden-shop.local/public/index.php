<?php
require_once __DIR__ . '/includes/functions.php';

$filters = [
    'light' => $_GET['light'] ?? '',
    'soil'  => $_GET['soil'] ?? '',
];
$products = getProducts($filters);

include __DIR__ . '/includes/header.php';
?>

<h1>🌱 Каталог растений</h1>
<p style="margin-bottom:15px; color:#666;">Поможем выбрать идеальное растение для вашего участка</p>

<a href="/quiz.php" class="btn-primary" style="margin-bottom:20px; display:inline-block;">
    ✨ Подберите растение за 5 шагов
</a>

<div class="filters">
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
        <select name="light" style="flex:1; min-width:150px;">
            <option value="">Освещённость</option>
            <option value="Солнце"   <?= $filters['light'] === 'Солнце'   ? 'selected' : '' ?>>Солнце</option>
            <option value="Полутень" <?= $filters['light'] === 'Полутень' ? 'selected' : '' ?>>Полутень</option>
            <option value="Тень"     <?= $filters['light'] === 'Тень'     ? 'selected' : '' ?>>Тень</option>
        </select>
        <select name="soil" style="flex:1; min-width:150px;">
            <option value="">Тип почвы</option>
            <option value="Чернозем"  <?= $filters['soil'] === 'Чернозем'  ? 'selected' : '' ?>>Чернозём</option>
            <option value="Глинистая" <?= $filters['soil'] === 'Глинистая' ? 'selected' : '' ?>>Глинистая</option>
            <option value="Песчаная"  <?= $filters['soil'] === 'Песчаная'  ? 'selected' : '' ?>>Песчаная</option>
        </select>
        <button type="submit" style="min-width:120px;">Применить</button>
        <a href="/index.php" style="padding:10px 15px; color:#666; text-decoration:none;">Сбросить</a>
    </form>
</div>

<div class="grid">
    <?php foreach ($products as $p): ?>
        <div class="card" id="product-card-<?= $p['id'] ?>">
            <a href="/product.php?id=<?= $p['id'] ?>" style="display: block; overflow: hidden;">
                <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
            </a>
            <div class="card-body">
                <a href="/product.php?id=<?= $p['id'] ?>" style="text-decoration:none; color:inherit;">
                    <h3><?= e($p['name']) ?></h3>
                </a>
                <p><?= e(mb_substr($p['description'], 0, 60)) ?>...</p>
                
                <div style="margin-bottom: 5px; display:flex; align-items: baseline; gap: 5px;">
                    <span class="price" style="font-size: 1.4rem;"><?= number_format($p['price'], 0, '.', ' ') ?> ₽</span>
                    <span style="font-size: 0.85rem; color: #888;">/ шт.</span>
                </div>
                
                <div style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">
                    <span class="stock-text" id="stock-display-<?= $p['id'] ?>">
                        <?php if ($p['stock_quantity'] > 0): ?>
                            <span style="color:#2e7d32;">● В наличии: <?= $p['stock_quantity'] ?> шт.</span>
                        <?php else: ?>
                            <span style="color:#c62828;">● Нет в наличии</span>
                        <?php endif; ?>
                    </span>
                </div>

                <div id="cart-controls-<?= $p['id'] ?>" style="display: <?= $p['stock_quantity'] > 0 ? 'block' : 'none' ?>;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom: 10px;">
                        <label style="font-size: 0.9rem; margin:0;">Кол-во:</label>
                        <input type="number" id="qty-<?= $p['id'] ?>" value="1" min="1" max="<?= $p['stock_quantity'] ?>" 
                               oninput="if(this.value > <?= $p['stock_quantity'] ?>) this.value = <?= $p['stock_quantity'] ?>; if(this.value < 1) this.value = 1;"
                               style="width: 70px; padding: 5px; margin:0; text-align: center; border: 1px solid #ccc; border-radius: 5px;">
                    </div>
                    
                    <!-- Передаем 5-й параметр: остаток на складе -->
                    <button onclick="addToCart(<?= $p['id'] ?>, '<?= e(addslashes($p['name'])) ?>', <?= $p['price'] ?>, document.getElementById('qty-<?= $p['id'] ?>').value, <?= $p['stock_quantity'] ?>)">
                        В корзину
                    </button>
                </div>

                <div id="no-stock-btn-<?= $p['id'] ?>" style="display: <?= $p['stock_quantity'] <= 0 ? 'block' : 'none' ?>;">
                    <button disabled style="background:#ccc; cursor:not-allowed; margin-bottom:10px; width:100%;">Нет в наличии</button>
                </div>

                <a href="/product.php?id=<?= $p['id'] ?>#related" 
                   style="display:block; text-align:center; margin-top:10px; background:#e0e0e0; color:#333; font-size:0.9rem; padding: 10px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    🔍 С чем похоже
                </a>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($products)): ?>
        <p>По вашему запросу ничего не найдено. <a href="/index.php">Сбросить фильтры</a></p>
    <?php endif; ?>
</div>

<script>
// Обновление остатков на лету (каждые 5 секунд)
async function updateStocksOnScreen() {
    try {
        const res = await fetch('/api/get_stock.php?t=' + new Date().getTime());
        const stocks = await res.json();

        for (const [id, quantity] of Object.entries(stocks)) {
            const display = document.getElementById('stock-display-' + id);
            const controls = document.getElementById('cart-controls-' + id);
            const noStockBtn = document.getElementById('no-stock-btn-' + id);
            const qtyInput = document.getElementById('qty-' + id);

            if (display) {
                if (quantity > 0) {
                    display.innerHTML = `<span style="color:#2e7d32;">● В наличии: ${quantity} шт.</span>`;
                    if (controls) controls.style.display = 'block';
                    if (noStockBtn) noStockBtn.style.display = 'none';
                    if (qtyInput) qtyInput.max = quantity;
                } else {
                    display.innerHTML = `<span style="color:#c62828;">● Нет в наличии</span>`;
                    if (controls) controls.style.display = 'none';
                    if (noStockBtn) noStockBtn.style.display = 'block';
                }
            }
        }
    } catch (e) {
        console.log('Ошибка обновления остатков:', e);
    }
}

setInterval(updateStocksOnScreen, 5000);

document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
        updateStocksOnScreen();
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>