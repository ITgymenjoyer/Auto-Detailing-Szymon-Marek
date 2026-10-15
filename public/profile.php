<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$error = '';
$success = '';
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if (empty($name) || empty($surname) || empty($phone)) {
        $error = 'Wszystkie pola poza hasłem są wymagane.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, surname = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$name, $surname, $phone, $user_id])) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_surname'] = $surname;
            $success = 'Profil został zaktualizowany.';
        } else {
            $error = 'Wystąpił błąd podczas aktualizacji profilu.';
        }
    }
}
$stmt = $pdo->prepare("SELECT name, surname, email, phone, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edycja profilu</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div style="max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
        <h2>Edycja Profilu</h2>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form action="profile.php" method="POST">
            <p><label>Imię:<br><input type="text" name="name" value="<?php echo htmlspecialchars($current_user['name']); ?>" required></label></p>
            <p><label>Nazwisko:<br><input type="text" name="surname" value="<?php echo htmlspecialchars($current_user['surname']); ?>" required></label></p>
            <p><label>E-mail (tylko do odczytu):<br><input type="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" disabled style="opacity: 0.6;"></label></p>
            <p><label>Telefon:<br><input type="text" name="phone" value="<?php echo htmlspecialchars($current_user['phone']); ?>" required></label></p>
            <p><button type="submit">Zapisz zmiany</button></p>
        </form>
        <p>
            <a href="index.php">Strona główna</a> | 
            <?php if ($current_user['role'] === 'admin'): ?>
                <a href="admin_panel.php">Panel Admina</a>
            <?php elseif ($current_user['role'] === 'employee'): ?>
                <a href="employee_panel.php">Panel Pracownika</a>
            <?php else: ?>
                <a href="client_panel.php">Panel Klienta</a>
            <?php endif; ?>
        </p>
    </div>
</body>
</html>