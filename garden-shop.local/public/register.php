<?php
require_once __DIR__ . '/includes/functions.php';

if (currentUser()) {
    header('Location: /account.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name     = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if (!$email || !$password) {
        $error = 'Заполните email и пароль';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Пользователь с таким email уже существует';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $hash, $name, $phone]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: /account.php');
            exit;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<h1>Регистрация</h1>

<div class="form-box">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email *</label>
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        <label>Пароль * (мин. 6 символов)</label>
        <input type="password" name="password" required minlength="6">
        <label>ФИО</label>
        <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>">
        <label>Телефон</label>
        <input type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
        <button type="submit" class="btn-primary" style="width:100%; margin-top:15px;">Зарегистрироваться</button>
    </form>

    <p style="margin-top:15px;">Уже есть аккаунт? <a href="/login.php">Войти</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>