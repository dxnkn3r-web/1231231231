<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$filters = [
    'light' => $_GET['light'] ?? '',
    'soil'  => $_GET['soil'] ?? '',
];

echo json_encode(getProducts($filters), JSON_UNESCAPED_UNICODE);