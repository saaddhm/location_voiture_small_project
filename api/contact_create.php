<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/sanitize.php';
require __DIR__ . '/../includes/csrf.php';

if (!csrf_verify()) {
    echo json_encode(['ok' => false, 'error' => 'Invalid security token']);
    exit;
}

$fullName = isset($_POST['full_name']) ? sanitize_string($_POST['full_name'], 120) : '';
$email    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
$phone    = isset($_POST['phone']) ? sanitize_phone((string) ($_POST['phone'] ?? '')) : null;
$subject  = isset($_POST['subject']) ? sanitize_string($_POST['subject'], 160) : '';
$message  = isset($_POST['message']) ? sanitize_string($_POST['message'], 5000) : '';

$errors = [];
if ($fullName === '') {
    $errors[] = 'Le nom est requis.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalide.';
}
if ($subject === '') {
    $errors[] = 'Le sujet est requis.';
}
if ($message === '') {
    $errors[] = 'Le message est requis.';
}

if ($errors !== []) {
    echo json_encode(['ok' => false, 'error' => implode(' ', $errors)]);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$fullName, $email, $phone ?: null, $subject, $message]);

$id = (int) $pdo->lastInsertId();
echo json_encode(['ok' => true, 'data' => ['id' => $id]]);
