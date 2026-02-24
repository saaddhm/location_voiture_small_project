<?php
declare(strict_types=1);

$storeKey = 'Amesip_a2023mesip'; // même clé que 2.SendData.php

$hashValid = false;
$hasPost = !empty($_POST);
$amount = isset($_POST['amount']) ? trim((string) $_POST['amount']) : '';
$oid = isset($_POST['oid']) ? trim((string) $_POST['oid']) : '';
$reservationId = (strpos($oid, 'RES-') === 0) ? (int) substr($oid, 4) : 0;

if ($hasPost && isset($_POST['HASH'])) {
    $postParams = array_keys($_POST);
    natcasesort($postParams);
    $hashval = '';
    foreach ($postParams as $param) {
        $paramValue = trim(html_entity_decode((string) $_POST[$param], ENT_QUOTES, 'UTF-8'));
        $escapedParamValue = str_replace('|', '\\|', str_replace('\\', '\\\\', $paramValue));
        $lowerParam = strtolower($param);
        if ($lowerParam !== 'hash' && $lowerParam !== 'encoding') {
            $hashval .= $escapedParamValue . '|';
        }
    }
    $escapedStoreKey = str_replace('|', '\\|', str_replace('\\', '\\\\', $storeKey));
    $hashval .= $escapedStoreKey;
    $calculatedHashValue = hash('sha512', $hashval);
    $actualHash = base64_encode(pack('H*', $calculatedHashValue));
    $retrievedHash = (string) $_POST['HASH'];
    $hashValid = hash_equals($actualHash, $retrievedHash);

    // Paiement accepté : mettre la réservation en "confirmed" et rediriger vers la page succès
    if ($hashValid && isset($_POST['ProcReturnCode']) && $_POST['ProcReturnCode'] === '00' && $reservationId > 0) {
        require __DIR__ . '/../config/database.php';
        $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->execute(['confirmed', $reservationId]);
        // Redirection directe vers la page ok_success (GET) pour afficher la confirmation
        $redirect = 'ok-success.php?success=1&oid=' . rawurlencode($oid) . '&amount=' . rawurlencode($amount);
        header('Location: ' . $redirect);
        exit;
    }
}

// Affichage après redirection (GET) : page succès sans avertissement hash
if (isset($_GET['success']) && (int) $_GET['success'] === 1) {
    $amount = isset($_GET['amount']) ? trim((string) $_GET['amount']) : '';
    $oid = isset($_GET['oid']) ? trim((string) $_GET['oid']) : '';
    $hashValid = true;
    $hasPost = false;
}

$baseUrl = '../';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement réussi | AutoLoc</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .result-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .result-card { max-width: 420px; width: 100%; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 2rem; text-align: center; box-shadow: var(--shadow); }
        .result-icon { width: 64px; height: 64px; margin: 0 auto 1.25rem; border-radius: 50%; background: rgba(63, 185, 80, 0.2); color: var(--color-success); display: flex; align-items: center; justify-content: center; font-size: 2rem; }
        .result-page h1 { margin: 0 0 0.5rem; font-size: 1.5rem; }
        .result-page p { color: var(--color-text-muted); margin: 0 0 0.5rem; }
        .result-page .amount { font-size: 1.25rem; font-weight: 600; color: var(--color-accent); margin: 0.5rem 0 1.5rem; }
        .result-page .btn { display: inline-block; padding: 0.6rem 1.25rem; background: var(--color-accent); color: #fff; text-decoration: none; border-radius: var(--radius); font-weight: 500; }
        .result-page .btn:hover { background: var(--color-accent-hover); }
        .hash-warn { margin-top: 1rem; padding: 0.75rem; background: rgba(248, 81, 73, 0.15); border-radius: var(--radius); font-size: 0.9rem; color: var(--color-error); }
    </style>
</head>
<body>
    <div class="result-page">
        <div class="result-card">
            <div class="result-icon" aria-hidden="true">✓</div>
            <h1>Paiement réussi</h1>
            <p>Votre paiement a bien été enregistré.</p>
            <?php if ($amount !== ''): ?>
                <p class="amount"><?php echo htmlspecialchars(number_format((float) $amount, 2, ',', ' '), ENT_QUOTES, 'UTF-8'); ?> MAD</p>
            <?php endif; ?>
            <p style="margin-bottom:1.5rem;">Référence : <?php echo htmlspecialchars($oid ?: '—', ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if ($hasPost && !$hashValid): ?>
                <p class="hash-warn">La signature de la réponse n’a pas pu être vérifiée.</p>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn">Retour à l’accueil</a>
        </div>
    </div>
</body>
</html>
