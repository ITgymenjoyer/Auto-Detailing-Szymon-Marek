<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    if (empty($name) || empty($surname) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Wszystkie pola są wymagane.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Niepoprawny format adresu e-mail.';
    } elseif ($password !== $password_confirm) {
        $error = 'Hasła nie są identyczne.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Ten adres e-mail jest już zajęty.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, surname, email, password, phone, role) VALUES (?, ?, ?, ?, ?, 'client')");
            if ($stmt->execute([$name, $surname, $email, $hashed_password, $phone])) {
                $success = 'Rejestracja zakończona sukcesem. Możesz się zalogować.';
            } else {
                $error = 'Wystąpił błąd serwera. Spróbuj ponownie.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Rejestracja - Auto Detailing</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div style="max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc;">
        <h2>Rejestracja Nowego Klienta</h2>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <p><label>Imię:<br><input type="text" name="name" required></label></p>
            <p><label>Nazwisko:<br><input type="text" name="surname" required></label></p>
            <p><label>E-mail:<br><input type="email" name="email" required></label></p>
            <p><label>Telefon:<br><input type="text" name="phone" required></label></p>
            <p><label>Hasło:<br><input type="password" name="password" required></label></p>
            <p><label>Powtórz hasło:<br><input type="password" name="password_confirm" required></label></p>
            <p><button type="submit">Zarejestruj się</button></p>
        </form>
        <p><a href="login.php">Masz już konto? Zaloguj się</a></p>
    </div>
</body>
</html>
