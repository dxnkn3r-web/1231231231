<?php
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['reset_csrf'])) {
    $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
}
$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!hash_equals($_SESSION['reset_csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Сессия устарела. Обновите страницу и попробуйте снова.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email.';
    } elseif (isset($_SESSION['reset_request_time']) && time() - $_SESSION['reset_request_time'] < 60) {
        $error = 'Подождите минуту перед повторным запросом.';
    } else {
        $_SESSION['reset_request_time'] = time();
        $sent = true;
        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $configFile = __DIR__ . '/config/mail.local.php';
        $mailConfig = is_file($configFile) ? require $configFile : [];
        if ($user && !empty($mailConfig['site_url']) && !empty($mailConfig['from']) && filter_var($mailConfig['from'], FILTER_VALIDATE_EMAIL)) {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR)) ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at)');
            $stmt->execute([$user['id'], hash('sha256', $token)]);
            $url = rtrim($mailConfig['site_url'], '/') . '/reset_password.php?token=' . $token;
            $subject = '=?UTF-8?B?' . base64_encode('Восстановление пароля — Зелёный Двор') . '?=';
            $body = "Здравствуйте!\n\nДля смены пароля перейдите по ссылке:\n" . $url . "\n\nСсылка действует один час. Если вы не запрашивали смену пароля, проигнорируйте это письмо.";
            $headers = "From: " . $mailConfig['from'] . "\r\nContent-Type: text/plain; charset=UTF-8";
            if (!mail($user['email'], $subject, $body, $headers)) {
                error_log('Не удалось отправить письмо восстановления пароля.');
            }
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
<h1>Восстановление пароля</h1>
<div class="form-box">
    <?php if ($sent): ?>
        <div class="alert alert-success">Если аккаунт с таким email существует, мы отправим ссылку для восстановления пароля.</div>
    <?php else: ?>
        <p>Укажите email, который вы использовали при регистрации. Мы отправим ссылку для смены пароля.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['reset_csrf']) ?>">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
            <button type="submit" class="btn-primary" style="width:100%; margin-top:15px;">Отправить ссылку</button>
        </form>
    <?php endif; ?>
    <p style="margin-top:15px;"><a href="/login.php">Вернуться ко входу</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
