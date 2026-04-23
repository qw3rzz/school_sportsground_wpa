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
            $stmt = $db->prepare("
                INSERT INTO uzivatele (jmeno, prijmeni, email, heslo_hash)
                VALUES (?, ?, ?, ?)
            ");
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
    <title>Registrace</title>
</head>
<body>
<h1>Registrace</h1>

<?php if ($uspech): ?>
    <p style="color:green"><?= $uspech ?></p>
    <a href="login.php">Přihlásit se</a>
<?php endif; ?>

<?php if (!empty($chyby)): ?>
    <ul style="color:red">
        <?php foreach ($chyby as $chyba): ?>
            <li><?= $chyba ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="POST" action="register.php">
    <label>Jméno:
        <input type="text" name="jmeno" required
               value="<?= htmlspecialchars($_POST['jmeno'] ?? '') ?>">
    </label><br><br>

    <label>Příjmení:
        <input type="text" name="prijmeni" required
               value="<?= htmlspecialchars($_POST['prijmeni'] ?? '') ?>">
    </label><br><br>

    <label>Email:
        <input type="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </label><br><br>

    <label>Heslo (min. 6 znaků):
        <input type="password" name="heslo" required minlength="6">
    </label><br><br>

    <label>Heslo znovu:
        <input type="password" name="heslo2" required minlength="6">
    </label><br><br>

    <button type="submit">Zaregistrovat se</button>
</form>

<p>Už máš účet? <a href="login.php">Přihlás se</a></p>
</body>
</html>