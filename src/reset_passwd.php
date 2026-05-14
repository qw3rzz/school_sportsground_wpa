<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['uzivatel_id'])) {
    header('Location: login.php');
    exit;
}

$chyby = [];
$uspech = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_heslo = $_POST['old_heslo'] ?? '';
    $heslo     = $_POST['heslo'] ?? '';
    $heslo2    = $_POST['heslo2'] ?? '';

    if (empty($old_heslo)) $chyby[] = 'Původní heslo je povinné.';
    if (strlen($heslo) < 6) $chyby[] = 'Nové heslo musí mít alespoň 6 znaků.';
    if ($heslo !== $heslo2) $chyby[] = 'Nová hesla se neshodují.';

    if (empty($chyby)) {
        $db = getDB();

        $stmt = $db->prepare("SELECT heslo_hash FROM uzivatele WHERE id = ?");
        $stmt->execute([$_SESSION['uzivatel_id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($old_heslo, $row['heslo_hash'])) {
            $chyby[] = 'Původní heslo se neshoduje.';
        } else {
            $new_hash = password_hash($heslo, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE uzivatele SET heslo_hash = ? WHERE id = ?");
            $stmt->execute([$new_hash, $_SESSION['uzivatel_id']]);

            session_regenerate_id(true);

            $uspech = 'Heslo bylo úspěšně změněno.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>SpotBook — Reset hesla</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="grid-bg"></div>

<div class="auth-container">
    <div class="auth-box">
        <div class="logo"><span class="spot">Spot</span><span class="book">Book</span></div>
        <div class="subtitle">Rezervační systém sportovišť</div>

        <h2>Resetování hesla</h2>

        <?php if (!empty($chyby)): ?>
            <div class="error-list">
                <ul>
                    <?php foreach ($chyby as $chyba): ?>
                        <li><?= htmlspecialchars($chyba) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($uspech): ?>
            <div class="success"><?= htmlspecialchars($uspech) ?></div>
        <?php endif; ?>

        <form method="POST" action="reset_passwd.php">

            <label>Původní heslo
                <input type="password" name="old_heslo" placeholder="••••••••" required>
            </label>

            <label>Nové heslo
                <input type="password" name="heslo" placeholder="••••••••" required>
            </label>

            <label>Potvrďte nové heslo
                <input type="password" name="heslo2" placeholder="••••••••" required>
            </label>

            <div class="divider"></div>

            <button type="submit" style="width:100%">Resetovat →</button>
        </form>
    </div>
</div>
</body>
</html>
