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

$carId    = isset($_POST['car_id']) ? sanitize_int($_POST['car_id']) : 0;
$fullName = isset($_POST['full_name']) ? sanitize_string($_POST['full_name'], 120) : '';
$email    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
$phone    = isset($_POST['phone']) ? sanitize_phone((string) ($_POST['phone'] ?? '')) : '';
$startDate = isset($_POST['start_date']) ? trim((string) $_POST['start_date']) : '';
$endDate   = isset($_POST['end_date']) ? trim((string) $_POST['end_date']) : '';
$note      = isset($_POST['note']) ? sanitize_string($_POST['note'], 2000) : '';
$paymentMethod = isset($_POST['payment_method']) ? trim((string) $_POST['payment_method']) : 'online';
if (!in_array($paymentMethod, ['online', 'cash'], true)) {
    $paymentMethod = 'online';
}

$errors = [];
if ($carId < 1) {
    $errors[] = 'Veuillez sélectionner une voiture.';
}
if ($fullName === '') {
    $errors[] = 'Le nom est requis.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalide.';
}
if ($phone === '') {
    $errors[] = 'Le téléphone est requis.';
}
if ($startDate === '') {
    $errors[] = 'La date de début est requise.';
}
if ($endDate === '') {
    $errors[] = 'La date de fin est requise.';
}

if ($errors !== []) {
    echo json_encode(['ok' => false, 'error' => implode(' ', $errors)]);
    exit;
}

$start = DateTime::createFromFormat('Y-m-d', $startDate);
$end   = DateTime::createFromFormat('Y-m-d', $endDate);
if (!$start || !$end || $start >= $end) {
    echo json_encode(['ok' => false, 'error' => 'Dates invalides.']);
    exit;
}

$days = (int) $start->diff($end)->days;
if ($days < 1) {
    $days = 1;
}

$stmt = $pdo->prepare('SELECT id, price_per_day FROM cars WHERE id = ? AND is_active = 1');
$stmt->execute([$carId]);
$car = $stmt->fetch();
if (!$car) {
    echo json_encode(['ok' => false, 'error' => 'Voiture introuvable.']);
    exit;
}

$pricePerDay = (float) $car['price_per_day'];
$total = round($days * $pricePerDay, 2);

$stmt = $pdo->prepare('INSERT INTO reservations (car_id, full_name, email, phone, start_date, end_date, days, total, note, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$carId, $fullName, $email, $phone, $startDate, $endDate, $days, $total, $note === '' ? null : $note, $paymentMethod]);

$id = (int) $pdo->lastInsertId();
echo json_encode([
    'ok'   => true,
    'data' => [
        'id'     => $id,
        'days'   => $days,
        'total'  => $total,
    ],
]);
