<?php

declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';

auth_required();
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id < 1) {
    echo json_encode(['ok' => false, 'error' => 'ID invalide']);
    exit;
}

if ($action === 'update_status') {
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['pending', 'confirmed', 'canceled'], true)) {
        echo json_encode(['ok' => false, 'error' => 'Statut invalide']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    echo json_encode(['ok' => true, 'data' => ['status' => $status]]);
    exit;
}

if ($action === 'delete_reservation') {
    $stmt = $pdo->prepare('DELETE FROM reservations WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Action inconnue']);
