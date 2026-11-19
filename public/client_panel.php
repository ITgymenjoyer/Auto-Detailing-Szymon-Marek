<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$error = '';
$success = '';
$todayStr = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation_id'])) {
    $reservationId = (int)$_POST['cancel_reservation_id'];

    $checkStmt = $pdo->prepare("
        SELECT id, reservation_date, start_time, status
        FROM reservations
        WHERE id = :id AND user_id = :user_id
        LIMIT 1
    ");
    $checkStmt->execute([
        'id' => $reservationId,
        'user_id' => $userId
    ]);
    $res = $checkStmt->fetch();

    if (!$res) {
        $error = "Nie znaleziono wskazanej rezerwacji.";
    } elseif ($res['status'] === 'cancelled' || $res['status'] === 'completed') {
        $error = "Tej rezerwacji nie można już anulować.";
    } elseif ($res['reservation_date'] < $todayStr) {
        $error = "Nie można anulować rezerwacji z przeszłości.";
    } else {
        $updateStmt = $pdo->prepare("
            UPDATE reservations
            SET status = 'cancelled'
            WHERE id = :id AND user_id = :user_id
        ");
        $updateStmt->execute([
            'id' => $reservationId,
            'user_id' => $userId
        ]);
        $success = "Rezerwacja została anulowana.";
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
        u.name AS emp_name,
        u.surname AS emp_surname
    FROM reservations r
    JOIN services s ON r.service_id = s.id
    JOIN users u ON r.employee_id = u.id
    WHERE r.user_id = :user_id
    ORDER BY r.reservation_date DESC, r.start_time DESC
");
$stmt->execute(['user_id' => $userId]);
$reservations = $stmt->fetchAll();

$upcomingReservations = [];
$historyReservations = [];

foreach ($reservations as $row) {
    $isUpcoming = ($row['reservation_date'] >= $todayStr) && !in_array($row['status'], ['cancelled', 'completed'], true);
    if ($isUpcoming) {
        $upcomingReservations[] = $row;
    } else {
        $historyReservations[] = $row;
    }
}

function formatStatusPl(string $status): string {
    return match ($status) {
        'pending' => 'Oczekująca',
        'confirmed' => 'Potwierdzona',
        'completed' => 'Zakończona',
        'cancelled' => 'Anulowana',
        default => $status,
    };
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Klienta</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/client_panel.css">
</head>
<body>
    <div class="panel-wrap">
        <div class="panel-header-card">
            <h1>Witaj <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Klient)</h1>
            <p>Zalogowałeś się poprawnie do panelu klienta auto detailingu.</p>
            <div class="panel-nav">
                <a href="index.php">Strona główna (Oferta)</a>
                <span>|</span>
                <a href="book.php">Nowa rezerwacja</a>
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

        <h2 class="section-title">Nadchodzące rezerwacje</h2>
        <?php if (empty($upcomingReservations)): ?>
            <div class="empty-box">Brak nadchodzących rezerwacji. Możesz umówić wizytę w zakładce <a href="book.php">Nowa rezerwacja</a>.</div>
        <?php else: ?>
            <?php foreach ($upcomingReservations as $res): ?>
                <div class="res-card">
                    <div class="res-top">
                        <span class="res-service"><?php echo htmlspecialchars($res['service_name']); ?></span>
                        <span class="status-badge status-<?php echo htmlspecialchars($res['status']); ?>">
                            <?php echo htmlspecialchars(formatStatusPl($res['status'])); ?>
                        </span>
                    </div>
                    <div class="res-meta">
                        <strong>Termin:</strong> <?php echo htmlspecialchars($res['reservation_date']); ?>
                        (<?php echo htmlspecialchars(substr($res['start_time'], 0, 5)); ?> – <?php echo htmlspecialchars(substr($res['end_time'], 0, 5)); ?>)
                    </div>
                    <div class="res-meta">
                        <strong>Pracownik:</strong> <?php echo htmlspecialchars($res['emp_name'] . ' ' . $res['emp_surname']); ?> |
                        <strong>Cena:</strong> <?php echo htmlspecialchars($res['price']); ?> PLN
                    </div>
                    <?php if (!empty($res['comment'])): ?>
                        <div class="res-meta"><strong>Uwagi:</strong> <?php echo htmlspecialchars($res['comment']); ?></div>
                    <?php endif; ?>

                    <form method="post" action="client_panel.php" class="cancel-form">
                        <input type="hidden" name="cancel_reservation_id" value="<?php echo (int)$res['id']; ?>">
                        <button type="submit" class="cancel-btn">Anuluj rezerwację</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2 class="section-title">Historia rezerwacji</h2>
        <?php if (empty($historyReservations)): ?>
            <div class="empty-box">Brak wcześniejszych lub anulowanych rezerwacji.</div>
        <?php else: ?>
            <?php foreach ($historyReservations as $res): ?>
                <div class="res-card history-card">
                    <div class="res-top">
                        <span class="res-service"><?php echo htmlspecialchars($res['service_name']); ?></span>
                        <span class="status-badge status-<?php echo htmlspecialchars($res['status']); ?>">
                            <?php echo htmlspecialchars(formatStatusPl($res['status'])); ?>
                        </span>
                    </div>
                    <div class="res-meta">
                        <strong>Termin:</strong> <?php echo htmlspecialchars($res['reservation_date']); ?>
                        (<?php echo htmlspecialchars(substr($res['start_time'], 0, 5)); ?> – <?php echo htmlspecialchars(substr($res['end_time'], 0, 5)); ?>)
                    </div>
                    <div class="res-meta">
                        <strong>Pracownik:</strong> <?php echo htmlspecialchars($res['emp_name'] . ' ' . $res['emp_surname']); ?> |
                        <strong>Cena:</strong> <?php echo htmlspecialchars($res['price']); ?> PLN
                    </div>
                    <?php if (!empty($res['comment'])): ?>
                        <div class="res-meta"><strong>Uwagi:</strong> <?php echo htmlspecialchars($res['comment']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
