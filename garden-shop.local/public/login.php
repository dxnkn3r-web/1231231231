<?php
require_once __DIR__ . '/includes/functions.php';

if (currentUser()) {
    header('Location: /account.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: /account.php');
            exit;
        } else {
            $error = 'Неверный email или пароль';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<h1>Вход</h1>

<div class="form-box">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        <label>Пароль</label>
        <input type="password" name="password" required>
        <button type="submit" class="btn-primary" style="width:100%; margin-top:15px;">Войти</button>
    </form>

    <p style="margin-top:15px;"><a href="/forgot_password.php">Забыли пароль?</a></p>
    <p style="margin-top:15px;">Нет аккаунта? <a href="/register.php">Зарегистрироваться</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>