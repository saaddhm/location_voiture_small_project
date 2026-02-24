<?php

declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';

if (auth_user() !== null) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Identifiants requis.';
    } elseif (auth_login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Identifiants incorrects.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Connexion</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .login-box { max-width: 360px; margin: 4rem auto; padding: 2rem; border: 1px solid #ddd; border-radius: 8px; }
        .login-box h1 { margin-bottom: 1.5rem; font-size: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.25rem; }
        .form-group input { width: 100%; padding: 0.5rem; box-sizing: border-box; }
        .error { color: #c00; margin-bottom: 1rem; }
        .btn { padding: 0.6rem 1.2rem; cursor: pointer; background: #333; color: #fff; border: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Administration</h1>
        <?php if ($error !== ''): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn">Connexion</button>
        </form>
    </div>
</body>
</html>
