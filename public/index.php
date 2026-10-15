<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->prepare("SELECT name, description, price, duration FROM services WHERE active = 1");
$stmt->execute();
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Diamond Detailing Studio - Oferta</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div style="text-align: right; padding: 10px; background: #f0f0f0;">
        <?php if (isset($_SESSION['user_id'])): ?>
            Zalogowany jako: <strong><?php echo htmlspecialchars($_SESSION['user_name'] . ' ' . $_SESSION['user_surname']); ?></strong> (<?php echo htmlspecialchars($_SESSION['user_role']); ?>) | 
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin_panel.php">Panel Admina</a>
            <?php elseif ($_SESSION['user_role'] === 'employee'): ?>
                <a href="employee_panel.php">Panel Pracownika</a>
            <?php else: ?>
                <a href="client_panel.php">Panel Klienta</a>
            <?php endif; ?>
            | <a href="logout.php">Wyloguj się</a>
        <?php else: ?>
            <a href="login.php">Logowanie</a> | <a href="register.php">Rejestracja</a>
        <?php endif; ?>
    </div>
    <h1>Nasza Oferta Auto Detailingu</h1>
    <div class="services-list">
        <?php if (empty($services)): ?>
            <p>Obecnie brak dostępnych usług.</p>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
                <div class="service-item">
                    <h2><?php echo htmlspecialchars($service['name']); ?></h2>
                    <p><?php echo htmlspecialchars($service['description']); ?></p>
                    <p><strong>Cena:</strong> <?php echo htmlspecialchars($service['price']); ?> PLN</p>
                    <p><strong>Czas trwania:</strong> <?php echo htmlspecialchars($service['duration']); ?> minut</p>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
