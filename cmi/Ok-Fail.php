<?php
declare(strict_types=1);

$storeKey = 'Amesip_a2023mesip'; // même clé que 2.SendData.php

$hashValid = false;
$hasPost = !empty($_POST);

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
}

$baseUrl = '../';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement échoué | AutoLoc</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .result-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .result-card { max-width: 420px; width: 100%; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius); padding: 2rem; text-align: center; box-shadow: var(--shadow); }
        .result-icon { width: 64px; height: 64px; margin: 0 auto 1.25rem; border-radius: 50%; background: rgba(248, 81, 73, 0.2); color: var(--color-error); display: flex; align-items: center; justify-content: center; font-size: 2rem; }
        .result-page h1 { margin: 0 0 0.5rem; font-size: 1.5rem; }
        .result-page p { color: var(--color-text-muted); margin: 0 0 1.5rem; }
        .result-page .btn { display: inline-block; padding: 0.6rem 1.25rem; background: var(--color-accent); color: #fff; text-decoration: none; border-radius: var(--radius); font-weight: 500; }
        .result-page .btn:hover { background: var(--color-accent-hover); }
        .hash-warn { margin-top: 1rem; padding: 0.75rem; background: rgba(248, 81, 73, 0.15); border-radius: var(--radius); font-size: 0.9rem; color: var(--color-error); }
    </style>
</head>
<body>
    <div class="result-page">
        <div class="result-card">
            <div class="result-icon" aria-hidden="true">✕</div>
            <h1>Paiement échoué</h1>
            <p>La transaction n’a pas abouti. Vous pouvez réessayer ou choisir un autre moyen de paiement.</p>
            <?php if ($hasPost && !$hashValid): ?>
                <p class="hash-warn">La signature de la réponse n’a pas pu être vérifiée.</p>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn">Retour à l’accueil</a>
        </div>
    </div>
</body>
</html>
