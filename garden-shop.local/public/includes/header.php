<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Интернет-магазин растений «Зелёный Двор». Подбор по почве, освещённости и климату.">
    <title>Зелёный Двор — интернет-магазин растений</title>
    <link rel="stylesheet" href="/assets/style.css">
    
    <!-- ЖЕСТКИЕ СТИЛИ ДЛЯ УВЕДОМЛЕНИЙ -->
    <style>
        header { display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; }
        .logo-link { text-decoration: none; color: white; display: flex; align-items: center; gap: 8px; font-size: 1.3rem; font-weight: bold; }
        .logo-link:hover { opacity: 0.8; }
        nav { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        nav a { color: white; text-decoration: none; font-weight: bold; font-size: 0.95rem; padding: 8px 15px; border-radius: 6px; background: rgba(255, 255, 255, 0.15); transition: background 0.2s; }
        nav a:hover { background: rgba(255, 255, 255, 0.3); text-decoration: none; }
        nav a.admin-link { background: #ffb300; color: #333; border: 1px solid #e6a100; }
        nav a.admin-link:hover { background: #ffa000; }

        /* Стили для всплывающих уведомлений */
        #custom-toast-container {
            position: fixed !important;
            top: 20px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            z-index: 999999 !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            pointer-events: none !important;
        }
        .custom-toast {
            background: #2e7d32 !important;
            color: white !important;
            padding: 12px 25px !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
            font-family: Arial, sans-serif !important;
            font-weight: bold !important;
            font-size: 1rem !important;
            margin-bottom: 10px !important;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease !important;
            pointer-events: auto !important;
        }
    </style>
</head>
<body>
<header>
    <a href="/index.php" class="logo-link">
        <span>🌿</span> Зелёный Двор
    </a>
    
    <nav>
        <a href="/index.php">Каталог</a>
        <a href="/quiz.php">Подберите растение</a>
        <a href="/cart.php">🛒 Корзина (<span id="cart-count">0</span>)</a>
        
        <?php $u = currentUser(); ?>
        <?php if ($u): ?>
            <a href="/account.php">👤 <?= e($u['full_name'] ?: $u['email']) ?></a>
            <?php if ($u['is_admin']): ?>
                <a href="/admin.php" class="admin-link">⚙️ Админ</a>
            <?php endif; ?>
            <a href="/logout.php">Выйти</a>
        <?php else: ?>
            <a href="/login.php">Войти</a>
        <?php endif; ?>
    </nav>
</header>

<!-- Скрипт уведомлений встроен прямо сюда, чтобы обойти кеш -->
<script>
    // Переопределяем старую функцию, если она вдруг загрузится
    window.showToast = function(message, duration = 2500) {
        // 1. Удаляем старые уведомления
        const oldToasts = document.querySelectorAll('.custom-toast');
        oldToasts.forEach(t => t.remove());

        // 2. Создаем контейнер сверху по центру
        let container = document.getElementById('custom-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'custom-toast-container';
            document.body.appendChild(container);
        }

        // 3. Создаем плашку
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        // Убираем эмодзи, если хотим строгий вид, или оставляем
        toast.textContent = message;
        
        container.appendChild(toast);

        // 4. Запускаем анимацию появления
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);

        // 5. Скрываем
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };
</script>

<!-- Принудительно грузим script.js с новой версией -->
<script src="/assets/script.js?v=<?= time() ?>"></script>

<main>