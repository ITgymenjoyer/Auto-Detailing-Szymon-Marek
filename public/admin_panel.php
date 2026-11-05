<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config/db.php';

$clientsCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$employeesCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();
$servicesCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$reservationsCount = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

$today = date('Y-m-d');
$todayReservationsCount = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date = ?");
$todayReservationsCount->execute([$today]);
$todayCount = $todayReservationsCount->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Administratora - Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div style="max-width: 800px; margin: 30px auto; padding: 20px;">
        <h1>Witaj <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Administrator)</h1>
        <p>
            <a href="index.php">Strona główna (Oferta)</a> | 
            <a href="admin_services.php">Zarządzaj Usługami i Kategoriami</a> | 
            <a href="admin_employees.php">Zarządzaj Pracownikami</a> | 
            <a href="admin_reservations.php">Zarządzaj Rezerwacjami</a> | 
            <a href="admin_users.php">Zarządzaj Użytkownikami</a> |
            <a href="profile.php">Edytuj profil</a> | 
            <a href="logout.php">Wyloguj się</a>
        </p>
        <hr>

        <h2>Dashboard - Statystyki systemu</h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px;">
            <div style="border: 1px solid #ccc; padding: 15px; border-radius: 6px; text-align: center;">
                <h3>Klienci</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $clientsCount; ?></p>
            </div>
            <div style="border: 1px solid #ccc; padding: 15px; border-radius: 6px; text-align: center;">
                <h3>Pracownicy</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $employeesCount; ?></p>
            </div>
            <div style="border: 1px solid #ccc; padding: 15px; border-radius: 6px; text-align: center;">
                <h3>Usługi</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $servicesCount; ?></p>
            </div>
            <div style="border: 1px solid #ccc; padding: 15px; border-radius: 6px; text-align: center;">
                <h3>Wszystkie rezerwacje</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $reservationsCount; ?></p>
            </div>
            <div style="border: 1px solid #ccc; padding: 15px; border-radius: 6px; text-align: center;">
                <h3>Rezerwacje na dziś</h3>
                <p style="font-size: 24px; font-weight: bold; color: #0066cc;"><?php echo $todayCount; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
