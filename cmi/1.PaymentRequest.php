<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';

$reservationId = isset($_GET['reservation_id']) ? (int) $_GET['reservation_id'] : 0;

if ($reservationId < 1) {
    echo 'Réservation invalide.';
    exit;
}

$stmt = $pdo->prepare('SELECT r.*, c.name AS car_name FROM reservations r JOIN cars c ON c.id = r.car_id WHERE r.id = ?');
$stmt->execute([$reservationId]);
$row = $stmt->fetch();

if (!$row) {
    echo 'Réservation introuvable.';
    exit;
}

$orgClientId  = '########'; // ID marchand CMI
$orgAmount = number_format((float) $row['total'], 2, '.', '');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$orgOkUrl = $scheme . '://' . $host . $dir . '/ok-success.php';
$orgFailUrl = $scheme . '://' . $host . $dir . '/Ok-Fail.php';
$orgCallbackUrl = $scheme . '://' . $host . $dir . '/callback.php';
$shopurl = $scheme . '://' . $host . dirname($dir);

$orgTransactionType = 'PreAuth';
$orgRnd = microtime();
$orgCurrency = '504'; // MAD

$billName = $row['full_name'];
$email = $row['email'];
$tel = $row['phone'];
$oid = 'RES-' . $row['id']; // unique par transaction
?>
<html>
<head>
  <title>3D PAY HOSTING</title>
  <meta http-equiv="Content-Language" content="tr">
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-9">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="now">
</head>
<body>
  <center>
    <form method="post" action="2.SendData.php">
      <table>
        <tr>
          <td align="center" colspan="2">
            <input type="submit" value="Complete Payment" id="submit" />
          </td>
        </tr>
      </table>

      <input type="hidden" name="clientid" value="<?php echo htmlspecialchars($orgClientId, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="amount" value="<?php echo htmlspecialchars($orgAmount, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="okUrl" value="<?php echo htmlspecialchars($orgOkUrl, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="failUrl" value="<?php echo htmlspecialchars($orgFailUrl, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="TranType" value="<?php echo htmlspecialchars($orgTransactionType, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="callbackUrl" value="<?php echo htmlspecialchars($orgCallbackUrl, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="shopurl" value="<?php echo htmlspecialchars($shopurl, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="currency" value="<?php echo htmlspecialchars($orgCurrency, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="rnd" value="<?php echo htmlspecialchars((string) $orgRnd, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="storetype" value="3D_PAY_HOSTING">
      <input type="hidden" name="hashAlgorithm" value="ver3">
      <input type="hidden" name="lang" value="fr">
      <input type="hidden" name="refreshtime" value="5">
      <input type="hidden" name="BillToName" value="<?php echo htmlspecialchars($billName, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="BillToCompany" value="Location voiture">
      <!-- <input type="hidden" name="BillToStreet1" value="Adresse principale"> -->
      <input type="hidden" name="BillToCity" value="Casablanca">
      <!-- <input type="hidden" name="BillToStateProv" value="Casablanca"> -->
      <input type="hidden" name="BillToPostalCode" value="20230">
      <input type="hidden" name="BillToCountry" value="504">
      <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="tel" value="<?php echo htmlspecialchars($tel, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="encoding" value="UTF-8">
      <input type="hidden" name="oid" value="<?php echo htmlspecialchars($oid, ENT_QUOTES, 'UTF-8'); ?>">
    </form>
  </center>
  <script>
    const button = document.getElementById('submit');
    if (button) {
      button.click();
    }
  </script>
</body>
</html>

