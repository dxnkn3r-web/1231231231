<?php
require_once __DIR__ . '/includes/functions.php';
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$token = $_POST['token'] ?? $_GET['token'] ?? '';
$validToken = is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token);
$error = '';
$success = false;
if (empty($_SESSION['reset_csrf'])) {
    $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
}
$reset = false;
if ($validToken) {
    $stmt = $pdo->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()');
    $stmt->execute([hash('sha256', $token)]);
    $reset = $stmt->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!hash_equals($_SESSION['reset_csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Сессия устарела. Обновите страницу и попробуйте снова.';
    } elseif (strlen($password) < 6 || strlen($password) > 72) {
        $error = 'Пароль должен содержать от 6 до 72 байт.';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают.';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW() FOR UPDATE');
            $stmt->execute([hash('sha256', $token)]);
            $locked = $stmt->fetch();
            if ($locked) {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_BCRYPT), $locked['user_id']]);
                $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$locked['user_id']]);
                $success = true;
            } else {
                $reset = false;
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
<h1>Новый пароль</h1>
<div class="form-box">
    <?php if ($success): ?>
        <div class="alert alert-success">Пароль изменён. Теперь можно войти.</div>
        <a href="/login.php">Войти</a>
    <?php elseif (!$reset): ?>
        <div class="alert alert-error">Ссылка недействительна или срок её действия истёк.</div>
        <a href="/forgot_password.php">Запросить новую ссылку</a>
    <?php else: ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="/reset_password.php">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['reset_csrf']) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label for="password">Новый пароль (не менее 6 символов)</label>
            <input id="password" type="password" name="password" required minlength="6" autocomplete="new-password">
            <label for="confirm_password">Повторите пароль</label>
            <input id="confirm_password" type="password" name="confirm_password" required minlength="6" autocomplete="new-password">
            <button type="submit" class="btn-primary" style="width:100%; margin-top:15px;">Сохранить пароль</button>
        </form>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
