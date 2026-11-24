<?php
/**
 * Заглушка: корзина хранится в localStorage браузера.
 * Файл нужен для возможного расширения (серверная корзина).
 */
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

echo json_encode([
    'success' => true,
    'message' => 'Товар добавлен',
    'item'    => $input
], JSON_UNESCAPED_UNICODE);