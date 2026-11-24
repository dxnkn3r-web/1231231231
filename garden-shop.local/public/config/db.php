<?php
/** Подключение к MySQL. Локальные настройки задаются в db.local.php. */
$settings = [
    'host' => '127.0.0.1',
    'port' => 3307,
    'database' => 'garden_shop',
    'user' => 'root',
    'password' => '',
];

$localConfig = __DIR__ . '/db.local.php';
if (is_file($localConfig)) {
    $settings = array_replace($settings, require $localConfig);
}

$dsn = 'mysql:host=' . $settings['host'] . ';port=' . $settings['port']
    . ';dbname=' . $settings['database'] . ';charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $settings['user'], $settings['password'], $options);
} catch (PDOException $e) {
    error_log('Ошибка подключения к БД: ' . $e->getMessage());
    http_response_code(500);
    exit('Ошибка подключения к базе данных. Проверьте настройки подключения.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
