<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['uzivatel_id'])) {
    header('Location: index.php');
    exit;
}

$chyby = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(htmlspecialchars($_POST['email'] ?? ''));
    $heslo = $_POST['heslo'] ?? '';

    if (empty($email)) $chyby[] = 'Email je povinný.';
    if (empty($heslo)) $chyby[] = 'Heslo je povinné.';

    if (empty($chyby)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM uzivatele WHERE email = ?");
        $stmt->execute([$email]);
        $uzivatel = $stmt->fetch();

        if ($uzivatel && password_verify($heslo, $uzivatel['heslo_hash'])) {
            $_SESSION['uzivatel_id']    = $uzivatel['id'];
            $_SESSION['uzivatel_jmeno'] = $uzivatel['jmeno'];
            $_SESSION['uzivatel_role']  = $uzivatel['role'];
            header('Location: index.php');
            exit;
        } else {
            $chyby[] = 'Nesprávný email nebo heslo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>SpotBook — Přihlášení</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="grid-bg"></div>

<div class="auth-container">
    <div class="auth-box">
        <div class="logo"><span class="spot">Spot</span><span class="book">Book</span></div>
        <div class="subtitle">Rezervační systém sportovišť</div>

        <h2>Přihlášení</h2>

        <?php if (!empty($chyby)): ?>
            <div class="error-list">
                <ul>
                    <?php foreach ($chyby as $chyba): ?>
                        <li><?= $chyba ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label>Email
                <input type="email" name="email" placeholder="tvuj@email.cz" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </label>

            <label>Heslo
                <input type="password" name="heslo" placeholder="••••••••" required>
            </label>

            <div class="divider"></div>

            <button type="submit" style="width:100%">Přihlásit se →</button>
        </form>

        <div class="auth-link">
            Nemáš účet? <a href="register.php">Zaregistruj se</a>
        </div>
    </div>
</div>
</body>
</html>