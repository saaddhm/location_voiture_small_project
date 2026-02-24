<?php

declare(strict_types=1);

function auth_init(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function auth_login(string $username, string $password): bool
{
    auth_init();
    require __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    return true;
}

function auth_logout(): void
{
    auth_init();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_user(): ?array
{
    auth_init();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
    ];
}

function auth_required(): void
{
    if (auth_user() === null) {
        header('Location: login.php');
        exit;
    }
}
