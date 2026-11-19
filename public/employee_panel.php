<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: login.php');
    exit;
}

$employeeId = (int)$_SESSION['user_id'];
$error = '';
$success = '';
$todayStr = date('Y-m-d');

$allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['new_status'])) {
    $reservationId = (int)$_POST['reservation_id'];
    $newStatus = $_POST['new_status'];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        $error = "Niepoprawny status rezerwacji.";
    } else {
        $checkStmt = $pdo->prepare("
            SELECT id, status
            FROM reservations
            WHERE id = :id AND employee_id = :employee_id
            LIMIT 1
        ");
        $checkStmt->execute([
            'id' => $reservationId,
            'employee_id' => $employeeId
        ]);
        $reservation = $checkStmt->fetch();

        if (!$reservation) {
            $error = "Nie znaleziono rezerwacji przypisanej do Twojego konta.";
        } else {
            $updateStmt = $pdo->prepare("
                UPDATE reservations
                SET status = :status
                WHERE id = :id AND employee_id = :employee_id
            ");
            $updateStmt->execute([
                'status' => $newStatus,
                'id' => $reservationId,
                'employee_id' => $employeeId
            ]);
            $success = "Zaktualizowano status rezerwacji.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.reservation_date,
        r.start_time,
        r.end_time,
        r.status,
        r.comment,
        s.name AS service_name,
        s.price,
        u.name AS client_name,
        u.surname AS client_surname,
        u.phone AS client_phone
    FROM reservations r
    JOIN services s ON r.service_id = s.id
    JOIN users u ON r.user_id = u.id
    WHERE r.employee_id = :employee_id
    ORDER BY r.reservation_date ASC, r.start_time ASC
");
$stmt->execute(['employee_id' => $employeeId]);
$reservations = $stmt->fetchAll();

$todayList = [];
$upcomingList = [];
$pastList = [];

foreach ($reservations as $row) {
    if ($row['reservation_date'] === $todayStr) {
        $todayList[] = $row;
    } elseif ($row['reservation_date'] > $todayStr) {
        $upcomingList[] = $row;
    } else {
        $pastList[] = $row;
    }
}

function formatEmpStatusPl(string $status): string {
    return match ($status) {
        'pending' => 'Oczekująca',
        'confirmed' => 'Zaakceptowana',
        'completed' => 'Zakończona',
        'cancelled' => 'Odrzucona / Anulowana',
        default => $status,
    };
}

function renderReservationCards(array $items, string $emptyText): void {
    if (empty($items)) {
        echo '<div class="empty-box">' . htmlspecialchars($emptyText) . '</div>';
        return;
    }

    foreach ($items as $res) {
        $status = $res['status'];
        echo '<div class="res-card">';
        echo '<div class="res-top">';
        echo '<span class="res-service">' . htmlspecialchars($res['service_name']) . '</span>';
        echo '<span class="status-badge status-' . htmlspecialchars($status) . '">' . htmlspecialchars(formatEmpStatusPl($status)) . '</span>';
        echo '</div>';

        echo '<div class="res-meta"><strong>Data i godziny:</strong> '
            . htmlspecialchars($res['reservation_date']) . ' ('
            . htmlspecialchars(substr($res['start_time'], 0, 5)) . ' – '
            . htmlspecialchars(substr($res['end_time'], 0, 5)) . ')</div>';

        echo '<div class="res-meta"><strong>Klient:</strong> '
            . htmlspecialchars($res['client_name'] . ' ' . $res['client_surname'])
            . ' | <strong>Tel:</strong> ' . htmlspecialchars($res['client_phone'])
            . ' | <strong>Cena:</strong> ' . htmlspecialchars($res['price']) . ' PLN</div>';

        if (!empty($res['comment'])) {
            echo '<div class="res-meta"><strong>Uwagi klienta:</strong> ' . htmlspecialchars($res['comment']) . '</div>';
        }

        echo '<div class="action-row">';
        echo '<form method="post" action="employee_panel.php" class="inline-form">';
        echo '<input type="hidden" name="reservation_id" value="' . (int)$res['id'] . '">';
        echo '<input type="hidden" name="new_status" value="confirmed">';
        echo '<button type="submit" class="btn-accept">Akceptuj</button>';
        echo '</form>';

        echo '<form method="post" action="employee_panel.php" class="inline-form">';
        echo '<input type="hidden" name="reservation_id" value="' . (int)$res['id'] . '">';
        echo '<input type="hidden" name="new_status" value="cancelled">';
        echo '<button type="submit" class="btn-decline">Odrzuć</button>';
        echo '</form>';

        echo '<form method="post" action="employee_panel.php" class="inline-form">';
        echo '<input type="hidden" name="reservation_id" value="' . (int)$res['id'] . '">';
        echo '<input type="hidden" name="new_status" value="completed">';
        echo '<button type="submit" class="btn-complete">Zakończona</button>';
        echo '</form>';
        echo '</div>';

        echo '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Pracownika</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/employee_panel.css">
</head>
<body>
    <div class="panel-wrap">
        <div class="panel-header-card">
            <h1>Witaj <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Pracownik)</h1>
            <p>Zalogowałeś się poprawnie do panelu pracownika.</p>
            <div class="panel-nav">
                <a href="index.php">Strona główna (Oferta)</a>
                <span>|</span>
                <a href="profile.php">Edytuj profil</a>
                <span>|</span>
                <a href="logout.php">Wyloguj się</a>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert-err"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert-ok"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <h2 class="section-title">Dzisiejsze wizyty</h2>
        <?php renderReservationCards($todayList, 'Brak wizyt zaplanowanych na dzisiaj.'); ?>

        <h2 class="section-title">Nadchodzące wizyty</h2>
        <?php renderReservationCards($upcomingList, 'Brak nadchodzących wizyt.'); ?>

        <h2 class="section-title">Wcześniejsze wizyty</h2>
        <?php renderReservationCards($pastList, 'Brak wcześniejszych wizyt.'); ?>
    </div>
</body>
</html>
