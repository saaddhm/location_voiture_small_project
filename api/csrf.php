<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../includes/csrf.php';

echo json_encode(['ok' => true, 'data' => ['csrf_token' => csrf_token()]]);
