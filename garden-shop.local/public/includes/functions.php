<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Текущий пользователь или null
 */
function currentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, email, full_name, phone, is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Проверка прав администратора
 */
function requireAdmin() {
    $user = currentUser();
    if (!$user || !$user['is_admin']) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Безопасный вывод HTML
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Получить товары с фильтрами
 */
function getProducts($filters = []) {
    global $pdo;
    $sql = "SELECT * FROM products WHERE stock_quantity > 0";
    $params = [];

    if (!empty($filters['light'])) {
        $sql .= " AND light_level = ?";
        $params[] = $filters['light'];
    }
    if (!empty($filters['soil'])) {
        $sql .= " AND soil_type = ?";
        $params[] = $filters['soil'];
    }

    $sql .= " ORDER BY price ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}