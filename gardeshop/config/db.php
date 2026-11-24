<?php
/**
 * Подключение к БД (OSPanel, MySQL).
 */

$host    = '127.0.0.1';    // ← 127.0.0.1, а не localhost
$port    = 3307;           // ← ВАШ ПОРТ (мы его только что увидели)
$db      = 'gardenshop';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}