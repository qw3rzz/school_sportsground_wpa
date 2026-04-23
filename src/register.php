<?php
session_start();
require_once 'config/database.php';

$chyby = [];
$uspech = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jmeno    = trim(htmlspecialchars($_POST['jmeno'] ?? ''));
    $prijmeni = trim(htmlspecialchars($_POST['prijmeni'] ?? ''));
    $email    = trim(htmlspecialchars($_POST['email'] ?? ''));
    $heslo    = $_POST['heslo'] ?? '';
    $heslo2   = $_POST['heslo2'] ?? '';

    if (empty($jmeno))    $chyby[] = 'Jméno je povinné.';
    if (empty($prijmeni)) $chyby[] = 'Příjmení je povinné.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $chyby[] = 'Neplatný email.';
    if (strlen($heslo) < 6) $chyby[] = 'Heslo musí mít alespoň 6 znaků.';
    if ($heslo !== $heslo2) $chyby[] = 'Hesla se neshodují.';

    if (empty($chyby)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM uzivatele WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $chyby[] = 'Tento email je již zaregistrován.';
        } else {
            $heslo_hash = password_hash($heslo, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO uzivatele (jmeno, prijmeni, email, heslo_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$jmeno, $prijmeni, $email, $heslo_hash]);
            $uspech = 'Registrace proběhla úspěšně!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>SpotBook — Registrace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="grid-bg"></div>

<div class="auth-container">
    <div class="auth-box">
        <div class="logo"><span class="spot">Spot</span><span class="book">Book</span></div>
        <div class="subtitle">Rezervační systém sportovišť</div>

        <h2>Registrace</h2>

        <?php if ($uspech): ?>
            <div class="success"><?= $uspech ?></div>
            <div class="auth-link">
                <a href="login.php">Přihlásit se →</a>
            </div>
        <?php else: ?>

            <?php if (!empty($chyby)): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($chyby as $chyba): ?>
                            <li><?= $chyba ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <label>Jméno
                    <input type="text" name="jmeno" placeholder="Jan" required
                           value="<?= htmlspecialchars($_POST['jmeno'] ?? '') ?>">
                </label>

                <label>Příjmení
                    <input type="text" name="prijmeni" placeholder="Novák" required
                           value="<?= htmlspecialchars($_POST['prijmeni'] ?? '') ?>">
                </label>

                <label>Email
                    <input type="email" name="email" placeholder="tvuj@email.cz" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </label>

                <label>Heslo (min. 6 znaků)
                    <input type="password" name="heslo" placeholder="••••••••" required minlength="6">
                </label>

                <label>Heslo znovu
                    <input type="password" name="heslo2" placeholder="••••••••" required minlength="6">
                </label>

                <div class="divider"></div>

                <button type="submit" style="width:100%">Zaregistrovat se →</button>
            </form>

            <div class="auth-link">
                Už máš účet? <a href="login.php">Přihlásit se</a>
            </div>

        <?php endif; ?>
    </div>
</div>
</body>
</html>