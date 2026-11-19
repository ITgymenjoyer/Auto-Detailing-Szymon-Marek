<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

$servicesStmt = $pdo->query("SELECT id, name, duration, price FROM services WHERE active = 1 ORDER BY name");
$allServices = $servicesStmt->fetchAll();

$selectedServiceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : (isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0);
$selectedEmployeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : (isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0);
$selectedDate = $_POST['reservation_date'] ?? ($_GET['reservation_date'] ?? '');
$selectedStartTime = $_POST['start_time'] ?? '';
$comment = trim($_POST['comment'] ?? '');

$selectedService = null;
foreach ($allServices as $srv) {
    if ((int)$srv['id'] === $selectedServiceId) {
        $selectedService = $srv;
        break;
    }
}

$employees = [];
if ($selectedService) {
    $empStmt = $pdo->prepare("
        SELECT u.id, u.name, u.surname
        FROM users u
        JOIN employee_services es ON u.id = es.employee_id
        WHERE es.service_id = :service_id
          AND u.role = 'employee'
          AND u.active = 1
        ORDER BY u.name, u.surname
    ");
    $empStmt->execute(['service_id' => $selectedService['id']]);
    $employees = $empStmt->fetchAll();

    $validEmployee = false;
    foreach ($employees as $emp) {
        if ((int)$emp['id'] === $selectedEmployeeId) {
            $validEmployee = true;
            break;
        }
    }
    if (!$validEmployee) {
        $selectedEmployeeId = 0;
    }
}

$availableSlots = [];
if ($selectedService && $selectedEmployeeId > 0 && $selectedDate !== '') {
    $dateObj = DateTime::createFromFormat('Y-m-d', $selectedDate);
    $todayStr = date('Y-m-d');

    if (!$dateObj || $dateObj->format('Y-m-d') !== $selectedDate) {
        $error = "Niepoprawny format daty.";
    } elseif ($selectedDate < $todayStr) {
        $error = "Nie można rezerwować terminów w przeszłości.";
    } else {
        $dayOfWeek = $dateObj->format('l');

        $availStmt = $pdo->prepare("
            SELECT start_time, end_time
            FROM employee_availability
            WHERE employee_id = :employee_id AND day_of_week = :day_of_week
        ");
        $availStmt->execute([
            'employee_id' => $selectedEmployeeId,
            'day_of_week' => $dayOfWeek
        ]);
        $availabilities = $availStmt->fetchAll();

        $resStmt = $pdo->prepare("
            SELECT start_time, end_time
            FROM reservations
            WHERE employee_id = :employee_id
              AND reservation_date = :reservation_date
              AND status != 'cancelled'
        ");
        $resStmt->execute([
            'employee_id' => $selectedEmployeeId,
            'reservation_date' => $selectedDate
        ]);
        $existingReservations = $resStmt->fetchAll();

        $durationSec = (int)$selectedService['duration'] * 60;
        $stepSec = 30 * 60;
        $nowTs = time();

        foreach ($availabilities as $avail) {
            $workStartTs = strtotime($selectedDate . ' ' . $avail['start_time']);
            $workEndTs   = strtotime($selectedDate . ' ' . $avail['end_time']);

            for ($slotStartTs = $workStartTs; $slotStartTs + $durationSec <= $workEndTs; $slotStartTs += $stepSec) {
                $slotEndTs = $slotStartTs + $durationSec;

                if ($selectedDate === $todayStr && $slotStartTs <= $nowTs) {
                    continue;
                }

                $hasConflict = false;
                foreach ($existingReservations as $res) {
                    $resStartTs = strtotime($selectedDate . ' ' . $res['start_time']);
                    $resEndTs   = strtotime($selectedDate . ' ' . $res['end_time']);

                    if ($slotStartTs < $resEndTs && $slotEndTs > $resStartTs) {
                        $hasConflict = true;
                        break;
                    }
                }

                if (!$hasConflict) {
                    $availableSlots[] = date('H:i', $slotStartTs);
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    if (!$selectedService) {
        $error = "Wybierz poprawną usługę.";
    } elseif ($selectedEmployeeId <= 0) {
        $error = "Wybierz pracownika.";
    } elseif (!in_array($selectedStartTime, $availableSlots, true)) {
        $error = "Wybrana godzina jest niedostępna lub koliduje z inną rezerwacją.";
    } else {
        $startTimeSql = $selectedStartTime . ':00';
        $startTs = strtotime($selectedDate . ' ' . $startTimeSql);
        $endTs = $startTs + ((int)$selectedService['duration'] * 60);
        $endTimeSql = date('H:i:s', $endTs);

        $checkStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM reservations
            WHERE employee_id = :employee_id
              AND reservation_date = :reservation_date
              AND status != 'cancelled'
              AND start_time < :new_end
              AND end_time > :new_start
        ");
        $checkStmt->execute([
            'employee_id' => $selectedEmployeeId,
            'reservation_date' => $selectedDate,
            'new_start' => $startTimeSql,
            'new_end' => $endTimeSql
        ]);

        if ((int)$checkStmt->fetchColumn() > 0) {
            $error = "Ten termin został właśnie zajęty. Wybierz inną godzinę.";
        } else {
            $insertStmt = $pdo->prepare("
                INSERT INTO reservations
                    (user_id, employee_id, service_id, reservation_date, start_time, end_time, status, comment)
                VALUES
                    (:user_id, :employee_id, :service_id, :reservation_date, :start_time, :end_time, 'pending', :comment)
            ");
            $insertStmt->execute([
                'user_id' => (int)$_SESSION['user_id'],
                'employee_id' => $selectedEmployeeId,
                'service_id' => (int)$selectedService['id'],
                'reservation_date' => $selectedDate,
                'start_time' => $startTimeSql,
                'end_time' => $endTimeSql,
                'comment' => $comment !== '' ? $comment : null
            ]);

            $success = "Rezerwacja została zapisana (" . htmlspecialchars($selectedDate) . " o " . htmlspecialchars($selectedStartTime) . "). Status: oczekująca.";
            $selectedStartTime = '';
            $comment = '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezerwacja wizyty - Diamond Detailing Studio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/book.css">
</head>
<body>
    <div class="booking-top-bar">
        <a href="index.php">&larr; Powrót do oferty</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client'): ?>
            <a href="client_panel.php">Panel Klienta &rarr;</a>
        <?php endif; ?>
    </div>

    <div class="booking-container">
        <div class="booking-card">
            <h1>Rezerwacja usługi</h1>
            <p class="booking-subtitle">Usługa &rarr; Pracownik &rarr; Data &rarr; Godzina &rarr; Potwierdzenie</p>

            <?php if ($error !== ''): ?>
                <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="post" action="book.php<?php echo $selectedServiceId > 0 ? '?service_id=' . $selectedServiceId : ''; ?>">
                <div class="step-block">
                    <label class="step-label" for="service_id">
                        <span class="step-num">1.</span> Wybierz usługę
                    </label>
                    <select name="service_id" id="service_id" onchange="this.form.submit()" required>
                        <option value="">-- Wybierz usługę --</option>
                        <?php foreach ($allServices as $srv): ?>
                            <option value="<?php echo (int)$srv['id']; ?>" <?php echo ((int)$srv['id'] === $selectedServiceId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($srv['name']); ?> (<?php echo (int)$srv['duration']; ?> min, <?php echo htmlspecialchars($srv['price']); ?> PLN)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if ($selectedService): ?>
                        <div class="service-meta">
                            <span><strong>Czas trwania:</strong> <?php echo (int)$selectedService['duration']; ?> min</span>
                            <span><strong>Cena:</strong> <?php echo htmlspecialchars($selectedService['price']); ?> PLN</span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($selectedService): ?>
                    <div class="step-block">
                        <label class="step-label" for="employee_id">
                            <span class="step-num">2.</span> Wybierz pracownika
                        </label>
                        <select name="employee_id" id="employee_id" onchange="this.form.submit()" required>
                            <option value="">-- Wybierz pracownika --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo (int)$emp['id']; ?>" <?php echo ((int)$emp['id'] === $selectedEmployeeId) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['name'] . ' ' . $emp['surname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($selectedService && $selectedEmployeeId > 0): ?>
                    <div class="step-block">
                        <label class="step-label" for="reservation_date">
                            <span class="step-num">3.</span> Wybierz datę
                        </label>
                        <input type="date" name="reservation_date" id="reservation_date"
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo htmlspecialchars($selectedDate); ?>"
                               onchange="this.form.submit()" required>
                    </div>
                <?php endif; ?>

                <?php if ($selectedService && $selectedEmployeeId > 0 && $selectedDate !== ''): ?>
                    <div class="step-block">
                        <label class="step-label" for="start_time">
                            <span class="step-num">4.</span> Wybierz godzinę
                        </label>
                        <?php if (empty($availableSlots)): ?>
                            <div class="msg-empty">Brak wolnych godzin w wybranym dniu.</div>
                        <?php else: ?>
                            <select name="start_time" id="start_time" required>
                                <option value="">-- Wybierz godzinę --</option>
                                <?php foreach ($availableSlots as $slot): ?>
                                    <option value="<?php echo htmlspecialchars($slot); ?>" <?php echo ($selectedStartTime === $slot) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($slot); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($availableSlots)): ?>
                        <div class="step-block">
                            <label class="step-label" for="comment">
                                <span class="step-num">5.</span> Uwagi (opcjonalnie)
                            </label>
                            <textarea name="comment" id="comment" rows="3" placeholder="Np. model auta, dodatkowe informacje..."><?php echo htmlspecialchars($comment); ?></textarea>
                        </div>
                        <div class="submit-booking">
                            <button type="submit" name="confirm_booking" value="1">Potwierdź rezerwację</button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>