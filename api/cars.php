<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../config/database.php';

$type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';

$validTypes = ['SUV', 'Berline', 'Eco', 'Luxe'];
if ($type !== '' && !in_array($type, $validTypes, true)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid type']);
    exit;
}

$sql = 'SELECT id, name, type, price_per_day, image, seats, transmission, fuel FROM cars WHERE is_active = 1';
$params = [];
if ($type !== '') {
    $sql .= ' AND type = ?';
    $params[] = $type;
}
$sql .= ' ORDER BY type, name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

echo json_encode(['ok' => true, 'data' => $cars]);
