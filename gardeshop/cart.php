<?php 
include __DIR__ . '/includes/header.php'; 

// Получаем актуальные остатки и ссылки на картинки для скрипта
$stmt = $pdo->query("SELECT id, stock_quantity, image_url FROM products");
$stocks = [];
$images = [];
while($row = $stmt->fetch()) {
    $stocks[$row['id']] = $row['stock_quantity'];
    $images[$row['id']] = $row['image_url'];
}
?>

<style>
    .cart-card {
        display: flex;
        align-items: center;
        background: #fff;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        gap: 20px;
        flex-wrap: wrap;
    }
    .cart-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        flex-shrink: 0;
        border: 1px solid #eee;
    }
    .cart-info {
        flex: 1;
        min-width: 200px;
    }
    .cart-title {
        font-size: 1.1rem;
        color: #333;
        font-weight: bold;
        text-decoration: none;
        margin-bottom: 5px;
        display: block;
    }
    .cart-title:hover { color: #2e7d32; }
    .cart-price {
        font-size: 1.2rem;
        color: #2e7d32;
        font-weight: bold;
    }
    .qty-controls {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
        background: #f9f9f9;
    }
    .qty-btn {
        background: #fff;
        border: none;
        width: 35px;
        height: 35px;
        font-size: 1.2rem;
        cursor: pointer;
        color: #333;
        transition: background 0.2s;
    }
    .qty-btn:hover { background: #e0e0e0; }
    .qty-input {
        width: 50px;
        text-align: center;
        border: none;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
        font-size: 1rem;
        font-weight: bold;
        margin: 0;
        padding: 5px;
        background: transparent;
        height: 35px;
    }
    .cart-total-price {
        font-size: 1.2rem;
        font-weight: bold;
        color: #333;
        min-width: 100px;
        text-align: right;
    }
    .cart-remove {
        background: #ffebee;
        color: #c62828;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background 0.2s;
    }
    .cart-remove:hover { background: #ffcdd2; }
    .summary-box {
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-top: 20px;
    }
    @media (max-width: 600px) {
        .cart-card { flex-direction: column; text-align: center; }
        .cart-info { min-width: 100%; }
        .cart-total-price { text-align: center; width: 100%; margin-top: 10px; }
        .qty-controls { margin: 0 auto; }
    }
</style>

<h1>🛒 Корзина</h1>

<div id="cart-container"></div>

<div class="summary-box" id="checkout-box" style="display:none;">
    <h2 style="margin-top:0; border-bottom: 2px solid #eee; padding-bottom: 15px;">Оформление заказа</h2>
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <span style="font-size:1.2rem; font-weight:bold;">Итого к оплате:</span>
        <span style="font-size:1.5rem; font-weight:bold; color:#2e7d32;" id="total-amount">0 ₽</span>
    </div>

    <form id="checkout-form">
        <label>Адрес доставки:</label>
        <textarea name="address" rows="3" required placeholder="Город, улица, дом, квартира"></textarea>
        <button type="submit" class="btn-primary" style="width:100%; margin-top:15px; padding:15px; font-size:1.1rem;">
            💳 Перейти к оплате
        </button>
    </form>
</div>

<script>
// Передаем остатки и картинки со склада в JavaScript
const PRODUCT_STOCKS = <?= json_encode($stocks) ?>;
const PRODUCT_IMAGES = <?= json_encode($images) ?>;

function renderCart() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const container = document.getElementById('cart-container');
    const checkoutBox = document.getElementById('checkout-box');
    const totalAmountEl = document.getElementById('total-amount');

    if (!cart.length) {
        container.innerHTML = `
            <div style="text-align:center; padding: 50px 20px; background:#fff; border-radius:10px;">
                <p style="font-size:1.2rem; color:#666; margin-bottom:20px;">В корзине пока пусто</p>
                <a href="/index.php" class="btn-primary">Перейти в каталог</a>
            </div>`;
        checkoutBox.style.display = 'none';
        return;
    }

    let total = 0;
    let html = '';
    
    cart.forEach((item, i) => {
        const sum = item.price * item.quantity;
        total += sum;

        // Берем maxStock из товара (сохранен при добавлении) или из базы
        const maxStock = item.maxStock || PRODUCT_STOCKS[item.id] || 999;
        const imgUrl = PRODUCT_IMAGES[item.id] || 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=200&auto=format&fit=crop';

        html += `
            <div class="cart-card">
                <img src="${imgUrl}" class="cart-img" alt="${item.name}" onerror="this.src='https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=200&auto=format&fit=crop'">
                
                <div class="cart-info">
                    <a href="/product.php?id=${item.id}" class="cart-title">${item.name}</a>
                    <div class="cart-price">${item.price.toLocaleString('ru-RU')} ₽ <span style="font-size:0.85rem; color:#888; font-weight:normal;">/ шт.</span></div>
                </div>

                <div class="qty-controls">
                    <button class="qty-btn" onclick="changeQty(${i}, -1)">−</button>
                    <input type="number" class="qty-input" value="${item.quantity}" min="1" max="${maxStock}" 
                           onchange="updateQtyDirect(${i}, this.value)">
                    <button class="qty-btn" onclick="changeQty(${i}, 1)">+</button>
                </div>

                <div class="cart-total-price">
                    ${sum.toLocaleString('ru-RU')} ₽
                </div>

                <button class="cart-remove" onclick="removeItem(${i})" title="Удалить товар">
                    🗑
                </button>
            </div>
        `;
    });

    container.innerHTML = html;
    totalAmountEl.textContent = total.toLocaleString('ru-RU') + ' ₽';
    checkoutBox.style.display = 'block';
}

function changeQty(index, delta) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let newQty = cart[index].quantity + delta;
    let maxStock = cart[index].maxStock || PRODUCT_STOCKS[cart[index].id] || 0;

    if (newQty > maxStock) {
        showToast(`⚠️ На складе только ${maxStock} шт.`);
        return;
    }

    cart[index].quantity = newQty;
    
    if (cart[index].quantity <= 0) {
        cart.splice(index, 1);
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    renderCart();
}

function updateQtyDirect(index, value) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let newQty = parseInt(value);
    let maxStock = cart[index].maxStock || PRODUCT_STOCKS[cart[index].id] || 0;

    if (isNaN(newQty) || newQty < 1) newQty = 1;
    if (newQty > maxStock) {
        newQty = maxStock;
        showToast(`⚠️ На складе только ${maxStock} шт.`);
    }

    cart[index].quantity = newQty;
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    renderCart();
}

function removeItem(index) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart.splice(index, 1);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    renderCart();
}

document.getElementById('checkout-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const address = e.target.address.value;

    const items = cart.map(i => ({ id: i.id, quantity: i.quantity }));

    try {
        const res = await fetch('/api/order_create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ items, address })
        });
        const json = await res.json();

        if (json.error) {
            alert('Ошибка: ' + json.error);
            return;
        }

        localStorage.removeItem('cart');
        alert('✅ Заказ №' + json.order_id + ' оформлен на сумму ' + json.total + ' ₽');
        window.location.href = '/account.php';
    } catch (err) {
        alert('Ошибка соединения с сервером.');
    }
});

renderCart();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>