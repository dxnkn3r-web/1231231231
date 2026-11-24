<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

// ============ ОБНОВЛЕНИЕ ТОВАРА (ЦЕНА И ОСТАТОК) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $stmt = $pdo->prepare("UPDATE products SET price = ?, stock_quantity = ? WHERE id = ?");
    $stmt->execute([$_POST['price'], $_POST['stock'], $_POST['id']]);
    header('Location: /admin.php?tab=products');
    exit;
}

// ============ ЗАГРУЗКА ФОТО ТОВАРА ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    $pid = (int)$_POST['product_id'];
    $file = $_FILES['image'] ?? null;

    if ($pid > 0 && $file && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $filename = 'product_' . $pid . '_' . time() . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $imgUrl = '/uploads/' . $filename;
                $stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");
                $stmt->execute([$imgUrl, $pid]);
            }
        }
    }
    header('Location: /admin.php?tab=products');
    exit;
}

// ============ СМЕНА ПАРОЛЯ ПОЛЬЗОВАТЕЛЯ ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $uid = (int)$_POST['user_id'];
    $new_pass = $_POST['new_password'];
    
    if ($uid > 0 && strlen($new_pass) >= 6) {
        $hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $uid]);
    }
    header('Location: /admin.php?tab=users');
    exit;
}

// ============ ДОБАВЛЕНИЕ МНОЖЕСТВЕННЫХ СВЯЗЕЙ ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_related_multiple'])) {
    $productIds = $_POST['product_ids'] ?? [];
    $relatedIds = $_POST['related_ids'] ?? [];
    
    if (!empty($productIds) && !empty($relatedIds)) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO related_products (product_id, related_id) VALUES (?, ?)");
        
        foreach ($productIds as $pid) {
            foreach ($relatedIds as $rid) {
                // Нельзя связать товар сам с собой
                if ($pid != $rid) {
                    $stmt->execute([(int)$pid, (int)$rid]);
                }
            }
        }
    }
    header('Location: /admin.php?tab=products');
    exit;
}

// ============ УДАЛЕНИЕ СВЯЗИ ============
if (isset($_GET['delete_related_pid'], $_GET['delete_related_rid'])) {
    $stmt = $pdo->prepare("DELETE FROM related_products WHERE product_id = ? AND related_id = ?");
    $stmt->execute([$_GET['delete_related_pid'], $_GET['delete_related_rid']]);
    header('Location: /admin.php?tab=products');
    exit;
}

// ============ СМЕНА СТАТУСА ЗАКАЗА ============
if (isset($_GET['order_status'], $_GET['order_id'])) {
    $allowed = ['pending', 'paid', 'shipped', 'cancelled'];
    if (in_array($_GET['order_status'], $allowed)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$_GET['order_status'], $_GET['order_id']]);
    }
    header('Location: /admin.php?tab=orders');
    exit;
}

// ============ УДАЛЕНИЕ ЗАКАЗА ============
if (isset($_GET['delete_order'])) {
    $oid = (int)$_GET['delete_order'];
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$oid]);
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$oid]);
    header('Location: /admin.php?tab=orders');
    exit;
}

// ============ ТЕСТ УМНОГО ПОДБОРА ============
$testResults = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_quiz'])) {
    $light = $_POST['t_light']; $soil = $_POST['t_soil']; $moisture = $_POST['t_moisture']; $frost = $_POST['t_frost'];
    $sql = "SELECT *, (CASE WHEN light_level = ? THEN 25 ELSE 0 END + CASE WHEN soil_type = ? THEN 25 ELSE 0 END + CASE WHEN moisture = ? THEN 25 ELSE 0 END + CASE WHEN frost_resistance = ? THEN 25 ELSE 0 END) AS match_score FROM products WHERE stock_quantity > 0 ORDER BY match_score DESC, price ASC LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$light, $soil, $moisture, $frost]);
    $testResults = $stmt->fetchAll();
}

// ============ ЗАГРУЗКА ДАННЫХ ============
$products = $pdo->query("SELECT * FROM products ORDER BY id ASC")->fetchAll();
$orders   = $pdo->query("SELECT o.*, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 50")->fetchAll();
$users    = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
$stats    = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total FROM orders WHERE status = 'paid'")->fetch();

// Загружаем связи с датой создания
$relatedData = $pdo->query("
    SELECT rp.product_id, rp.related_id, rp.created_at, p1.name as p1_name, p2.name as p2_name 
    FROM related_products rp 
    JOIN products p1 ON rp.product_id = p1.id 
    JOIN products p2 ON rp.related_id = p2.id 
    ORDER BY rp.created_at DESC, rp.product_id
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<style>
    .admin-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; flex-wrap: wrap; }
    .admin-tab { padding: 10px 20px; background: #fff; border: 1px solid #ddd; border-bottom: none; border-radius: 5px 5px 0 0; cursor: pointer; font-weight: bold; color: #666; text-decoration: none; margin-bottom: -2px; }
    .admin-tab:hover { background: #f0f0f0; }
    .admin-tab.active { background: #2e7d32; color: white; border-color: #2e7d32; }
    .tab-content { display: none; animation: fadeIn 0.3s; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    
    /* Стили для чекбоксов в связях */
    .checkbox-list label { display: flex; align-items: center; gap: 8px; padding: 5px; cursor: pointer; border-radius: 4px; transition: background 0.2s; }
    .checkbox-list label:hover { background: #e0e0e0; }
    .checkbox-list input[type="checkbox"] { width: 18px; height: 18px; margin: 0; cursor: pointer; }
</style>

<h1>⚙️ Админ-панель</h1>

<div class="admin-tabs">
    <a href="?tab=products" class="admin-tab" id="tab-btn-products">📦 Товары</a>
    <a href="?tab=orders" class="admin-tab" id="tab-btn-orders">📋 Заказы</a>
    <a href="?tab=users" class="admin-tab" id="tab-btn-users">👥 Пользователи</a>
    <a href="?tab=settings" class="admin-tab" id="tab-btn-settings">⚙️ Умный подбор</a>
</div>

<!-- ================= ВКЛАДКА: ТОВАРЫ ================= -->
<div id="tab-products" class="tab-content">
    
    <!-- Блок связей (Множественный выбор) -->
    <div class="card" style="padding:20px; margin-bottom:20px;">
        <h2 style="margin-top:0;">🔗 Похожие растения (Множественный выбор)</h2>
        <p style="color:#666; font-size:0.9rem; margin-bottom:15px;">Отметьте галочками растения слева и справа. Скрипт создаст связи для всех выбранных комбинаций.</p>
        
        <form method="POST">
            <input type="hidden" name="add_related_multiple" value="1">
            
            <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:20px;">
                <!-- Левая колонка -->
                <div style="flex:1; min-width:300px; background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #ddd;">
                    <h3 style="margin-top:0; font-size:1rem; color:#2e7d32;">1. Основные товары</h3>
                    <div class="checkbox-list" style="max-height: 200px; overflow-y: auto; padding-right:10px;">
                        <?php foreach ($products as $p): ?>
                            <label>
                                <input type="checkbox" name="product_ids[]" value="<?= $p['id'] ?>">
                                <span><?= e($p['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Центр -->
                <div style="display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:1.5rem; color:#666;">
                    ➡️
                </div>

                <!-- Правая колонка -->
                <div style="flex:1; min-width:300px; background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #ddd;">
                    <h3 style="margin-top:0; font-size:1rem; color:#2e7d32;">2. Похожие товары</h3>
                    <div class="checkbox-list" style="max-height: 200px; overflow-y: auto; padding-right:10px;">
                        <?php foreach ($products as $p): ?>
                            <label>
                                <input type="checkbox" name="related_ids[]" value="<?= $p['id'] ?>">
                                <span><?= e($p['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width:100%; padding:15px; font-size:1.1rem;">
                ➕ Создать все выбранные связи
            </button>
        </form>

        <!-- Таблица существующих связей -->
        <h3 style="margin-top:30px;">Текущие связи</h3>
        <div style="max-height: 300px; overflow-y: auto; background:#fff; border-radius:8px; border:1px solid #eee;">
            <table style="font-size:0.9rem; margin:0; width:100%;">
                <thead style="position: sticky; top: 0; background: #f4f4f4; z-index: 1;">
                    <tr>
                        <th style="padding:10px;">Основное растение</th>
                        <th style="padding:10px;">Похожие растения</th>
                        <th style="padding:10px; text-align:right;">Дата добавления</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Группируем связи по основному товару
                    $groupedRelations = [];
                    foreach ($relatedData as $r) {
                        $pid = $r['product_id'];
                        if (!isset($groupedRelations[$pid])) {
                            $groupedRelations[$pid] = [
                                'p1_name' => $r['p1_name'],
                                'related' => [],
                                'date' => $r['created_at'] // Берем дату первой связи
                            ];
                        }
                        // Добавляем название связанного товара
                        $groupedRelations[$pid]['related'][] = [
                            'id' => $r['related_id'],
                            'name' => $r['p2_name']
                        ];
                    }
                    ?>

                    <?php if (empty($groupedRelations)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#999; padding:20px;">Связей пока нет</td></tr>
                    <?php else: ?>
                        <?php foreach ($groupedRelations as $pid => $group): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding:10px; vertical-align: top;"><b><?= e($group['p1_name']) ?></b></td>
                                <td style="padding:10px;">
                                    <!-- Выводим связанные растения через запятую -->
                                    <?php foreach ($group['related'] as $index => $rel): ?>
                                        <span style="display:inline-block; margin: 2px 5px 2px 0; background:#e8f5e9; color:#2e7d32; padding:3px 8px; border-radius:4px; font-size:0.85rem;">
                                            <?= e($rel['name']) ?>
                                            <a href="?delete_related_pid=<?= $pid ?>&delete_related_rid=<?= $rel['id'] ?>" 
                                               onclick="return confirm('Удалить связь?')" 
                                               style="color:#c62828; text-decoration:none; margin-left:5px; font-weight:bold;"
                                               title="Удалить связь">×</a>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td style="padding:10px; text-align:right; color:#888; font-size:0.8rem; vertical-align: top;">
                                    <?= date('d.m.Y H:i', strtotime($group['date'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Таблица товаров -->
    <h2>📦 Список товаров</h2>
    <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Фото</th><th>ID</th><th>Название</th><th>Цена</th><th>Остаток</th><th>Действия</th><th>Новое фото</th></tr></thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td style="text-align:center;"><img src="<?= e($p['image_url']) ?>" style="width:50px; height:50px; object-fit:cover; border-radius:5px; border:1px solid #ddd;"></td>
                        <form method="POST" style="display:contents;">
                            <td><?= $p['id'] ?></td>
                            <td><?= e($p['name']) ?></td>
                            <td><input type="number" name="price" value="<?= $p['price'] ?>" style="width:80px; margin:0;"></td>
                            <td><input type="number" name="stock" value="<?= $p['stock_quantity'] ?>" style="width:60px; margin:0;"></td>
                            <td>
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" name="update_product" value="1" title="Сохранить">💾</button>
                            </td>
                        </form>
                        <td>
                            <form method="POST" enctype="multipart/form-data" style="display:flex; gap:5px; margin:0;">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="upload_image" value="1">
                                <input type="file" name="image" accept="image/jpeg, image/png, image/webp" required style="font-size:0.75rem; margin:0; padding:3px; width:130px;">
                                <button type="submit" style="padding:5px; font-size:0.8rem;" title="Загрузить">📸</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= ВКЛАДКА: ЗАКАЗЫ ================= -->
<div id="tab-orders" class="tab-content">
    <div class="card" style="padding:20px; margin-bottom:20px;">
        <h2 style="margin-top:0;">📊 Статистика</h2>
        <p>Оплаченных заказов: <b><?= $stats['cnt'] ?></b> | Общая выручка: <b><?= number_format($stats['total'], 0, '.', ' ') ?> ₽</b></p>
    </div>

    <h2>📋 История заказов</h2>
    <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>#</th><th>Клиент</th><th>Сумма</th><th>Статус (изменить)</th><th>Действия</th></tr></thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="5" style="text-align:center; color:#999;">Заказов нет</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <?php $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?"); $stmt->execute([$o['id']]); $items = $stmt->fetchAll(); ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= e($o['email'] ?? 'Гость') ?></td>
                            <td><?= number_format($o['total_amount'], 0, '.', ' ') ?> ₽</td>
                            <td>
                                <form method="GET" style="margin:0;">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="order_status" onchange="this.form.submit()" style="padding:5px; font-size:0.85rem; margin:0; width:auto;">
                                        <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>⏳ Ожидает</option>
                                        <option value="paid" <?= $o['status'] === 'paid' ? 'selected' : '' ?>>✅ Оплачен</option>
                                        <option value="shipped" <?= $o['status'] === 'shipped' ? 'selected' : '' ?>>📦 Отправлен</option>
                                        <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>❌ Отменён</option>
                                    </select>
                                </form>
                            </td>
                            <td style="white-space: nowrap;">
                                <button onclick="var r = document.getElementById('order-<?= $o['id'] ?>'); r.style.display = r.style.display === 'none' ? 'table-row' : 'none';" style="padding:5px 10px; font-size:0.85rem; background:#666;">👁 Состав</button>
                                <a href="?delete_order=<?= $o['id'] ?>" onclick="return confirm('Удалить заказ #<?= $o['id'] ?>?')" style="color:#c62828; margin-left:10px; font-size:0.85rem; text-decoration:none;">🗑 Удалить</a>
                            </td>
                        </tr>
                        <tr id="order-<?= $o['id'] ?>" style="display:none; background:#f9f9f9;">
                            <td colspan="5" style="padding: 15px 30px;">
                                <b>📍 Адрес:</b> <?= e($o['delivery_address'] ?: 'не указан') ?><br>
                                <b>📅 Дата:</b> <?= date('d.m.Y H:i', strtotime($o['created_at'])) ?><br>
                                <b>🌿 Состав:</b>
                                <ul style="margin-top:5px; margin-left:20px;">
                                    <?php foreach ($items as $i): ?>
                                        <li><?= e($i['product_name']) ?> — <b><?= $i['quantity'] ?> шт</b> × <?= number_format($i['price_at_moment'], 0, '.', ' ') ?> ₽ = <?= number_format($i['quantity'] * $i['price_at_moment'], 0, '.', ' ') ?> ₽</li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= ВКЛАДКА: ПОЛЬЗОВАТЕЛИ ================= -->
<div id="tab-users" class="tab-content">
    <h2>👥 Пользователи (<?= count($users) ?>)</h2>
    <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>ID</th><th>Email</th><th>Имя</th><th>Роль</th><th>Сменить пароль</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['full_name'] ?: '—') ?></td>
                        <td><?= $u['is_admin'] ? '👑 Админ' : 'Покупатель' ?></td>
                        <td style="width: 250px;">
                            <form method="POST" style="display:flex; gap:5px; margin:0;">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="change_password" value="1">
                                <input type="password" name="new_password" placeholder="Новый пароль" minlength="6" required style="padding:5px; font-size:0.85rem; margin:0;">
                                <button type="submit" style="padding:5px 10px; font-size:0.85rem;" title="Сменить">🔑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= ВКЛАДКА: УМНЫЙ ПОДБОР ================= -->
<div id="tab-settings" class="tab-content">
    <div class="card" style="padding:20px;">
        <h2 style="margin-top:0;">🧠 Тест умного подбора</h2>
        <p style="margin-bottom:15px; color:#666;">Проверьте, какие растения система предложит клиенту.</p>
        <form method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <input type="hidden" name="test_quiz" value="1">
            <div><label>Освещённость</label><select name="t_light" style="margin-bottom:0;"><option value="Солнце">☀️ Солнце</option><option value="Полутень">⛅ Полутень</option><option value="Тень">🌑 Тень</option></select></div>
            <div><label>Почва</label><select name="t_soil" style="margin-bottom:0;"><option value="Чернозем">🟤 Чернозём</option><option value="Глинистая">🟫 Глинистая</option><option value="Песчаная">🟨 Песчаная</option></select></div>
            <div><label>Влажность</label><select name="t_moisture" style="margin-bottom:0;"><option value="Влажная">💧 Влажная</option><option value="Засухоустойчивая">🌵 Засухоустойчивая</option></select></div>
            <div><label>Морозостойкость</label><select name="t_frost" style="margin-bottom:0;"><option value="Высокая">❄️ Высокая</option><option value="Средняя">🌡️ Средняя</option></select></div>
            <button type="submit" class="btn-primary" style="grid-column: span 2; margin-top:10px;">🔍 Запустить подбор</button>
        </form>
        <?php if ($testResults !== null): ?>
            <div style="margin-top:20px; padding-top:20px; border-top: 1px solid #ccc;">
                <h3 style="margin-top:0;">Результат:</h3>
                <?php if(empty($testResults)): ?><p>Ничего не найдено.</p><?php else: ?>
                    <ul style="margin-left: 20px;">
                        <?php foreach ($testResults as $r): ?>
                            <li style="margin-bottom:5px;"><b><?= e($r['name']) ?></b> — <span style="color:<?= $r['match_score'] > 50 ? '#2e7d32' : '#c62828' ?>; font-weight:bold;"><?= $r['match_score'] ?>%</span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Скрипт для переключения вкладок
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let activeTab = urlParams.get('tab') || 'products';

        if (window.location.hash === '#users') activeTab = 'users';
        if (window.location.hash === '#orders') activeTab = 'orders';
        if (window.location.hash === '#related') activeTab = 'products';

        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        const btn = document.getElementById('tab-btn-' + activeTab);
        const content = document.getElementById('tab-' + activeTab);
        
        if (btn && content) {
            btn.classList.add('active');
            content.classList.add('active');
        } else {
            document.getElementById('tab-btn-products').classList.add('active');
            document.getElementById('tab-products').classList.add('active');
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>