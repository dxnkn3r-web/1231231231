// ============ КОРЗИНА (localStorage) ============

function getCart() {
    return JSON.parse(localStorage.getItem('cart')) || [];
}

function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

function updateCartCount() {
    const cart = getCart();
    const total = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.querySelectorAll('#cart-count').forEach(el => el.textContent = total);
}

// Теперь функция принимает 5-й параметр maxStock (лимит со склада)
function addToCart(id, name, price, qty = 1, maxStock = 999) {
    const cart = getCart();
    const existing = cart.find(i => i.id === id);
    const quantityToAdd = parseInt(qty) || 1;

    if (existing) {
        // Проверяем, не превысит ли новое количество остаток на складе
        if (existing.quantity + quantityToAdd > maxStock) {
            showToast(`⚠️ На складе только ${maxStock} шт.`);
            return; // Прерываем добавление
        }
        existing.quantity += quantityToAdd;
        existing.maxStock = maxStock; // Обновляем лимит
    } else {
        cart.push({ id, name, price, quantity: quantityToAdd, maxStock: maxStock });
    }

    saveCart(cart);
    showToast(`✅ ${name} (${quantityToAdd} шт) добавлен в корзину`);

    if (typeof ym !== 'undefined') {
        ym(0, 'reachGoal', 'ADD_TO_CART');
    }
}

function removeFromCart(id) {
    let cart = getCart();
    cart = cart.filter(i => i.id !== id);
    saveCart(cart);
}

function clearCart() {
    localStorage.removeItem('cart');
    updateCartCount();
}

// ============ УВЕДОМЛЕНИЯ ============
window.showToast = function(message, duration = 2500) {
    const oldToasts = document.querySelectorAll('.custom-toast');
    oldToasts.forEach(t => t.remove());

    let container = document.getElementById('custom-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'custom-toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'custom-toast';
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

document.addEventListener('DOMContentLoaded', () => {
    updateCartCount();
});