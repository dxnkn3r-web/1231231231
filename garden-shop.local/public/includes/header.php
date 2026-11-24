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
<header class="site-header">
    <div class="site-header__inner">
        <a href="/index.php" class="brand" aria-label="Зелёный Двор — на главную">
            <img class="brand__logo" src="/assets/logo.webp" alt="">
            <span class="brand__text"><strong>Зелёный Двор</strong><span>Магазин растений</span></span>
        </a>

        <nav class="site-nav" aria-label="Основная навигация">
            <a href="/index.php">Каталог</a>
            <a href="/quiz.php">Подобрать растение</a>
            <a href="/cart.php" class="site-nav__cart">Корзина <span id="cart-count" class="site-nav__count">0</span></a>

            <?php $u = currentUser(); ?>
            <?php if ($u): ?>
                <a href="/account.php"><?= e($u['full_name'] ?: $u['email']) ?></a>
                <?php if ($u['is_admin']): ?>
                    <a href="/admin.php" class="admin-link">Админ</a>
                <?php endif; ?>
                <a href="/logout.php">Выйти</a>
            <?php else: ?>
                <a href="/login.php">Войти</a>
            <?php endif; ?>
        </nav>
    </div>
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