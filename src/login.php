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
            $_SESSION['uzivatel_id']  = $uzivatel['id'];
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
    <title>Přihlášení</title>
</head>
<body>
<h1>Přihlášení</h1>

<?php if (!empty($chyby)): ?>
    <ul style="color:red">
        <?php foreach ($chyby as $chyba): ?>
            <li><?= $chyba ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="POST" action="login.php">
    <label>Email:
        <input type="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </label><br><br>

    <label>Heslo:
        <input type="password" name="heslo" required>
    </label><br><br>

    <button type="submit">Přihlásit se</button>
</form>

<p>Nemáš účet? <a href="register.php">Zaregistruj se</a></p>
</body>
</html>