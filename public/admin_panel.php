<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Administratora</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div style="max-width: 600px; margin: 50px auto; padding: 20px;">
        <h1>Witaj <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Administrator)</h1>
        <p>Zalogowałeś się poprawnie do panelu zarządzania studiem.</p>
        <p>
            <a href="index.php">Strona główna (Oferta)</a> | 
            <a href="profile.php">Edytuj profil</a> | 
            <a href="logout.php">Wyloguj się</a>
        </p>
    </div>
</body>
</html>