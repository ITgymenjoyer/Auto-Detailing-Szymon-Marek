<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $error = 'Wprowadź e-mail i hasło.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, surname, password, role, active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            if ((int)$user['active'] === 0) {
                $error = 'Konto jest nieaktywne.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_surname'] = $user['surname'];
                $_SESSION['user_role'] = $user['role'];
                if ($user['role'] === 'admin') {
                    header('Location: admin_panel.php');
                } elseif ($user['role'] === 'employee') {
                    header('Location: employee_panel.php');
                } else {
                    header('Location: client_panel.php');
                }
                exit;
            }
        } else {
            $error = 'Błędny adres e-mail lub hasło.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie - Auto Detailing</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div style="max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc;">
        <h2>Logowanie</h2>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <p><label>E-mail:<br><input type="email" name="email" required></label></p>
            <p><label>Hasło:<br><input type="password" name="password" required></label></p>
            <p><button type="submit">Zaloguj się</button></p>
        </form>
        <p><a href="register.php">Nie masz konta? Zarejestruj się</a></p>
    </div>
</body>
</html>
