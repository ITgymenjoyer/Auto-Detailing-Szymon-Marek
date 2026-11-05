<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

if (isset($_GET['delete_emp_id']) && isset($_GET['delete_srv_id'])) {
    $emp_id = (int)$_GET['delete_emp_id'];
    $srv_id = (int)$_GET['delete_srv_id'];
    $stmt = $pdo->prepare("DELETE FROM employee_services WHERE employee_id = ? AND service_id = ?");
    $stmt->execute([$emp_id, $srv_id]);
    header('Location: admin_employees.php');
    exit;
}

if (isset($_GET['delete_availability'])) {
    $av_id = (int)$_GET['delete_availability'];
    $stmt = $pdo->prepare("DELETE FROM employee_availability WHERE id = ?");
    $stmt->execute([$av_id]);
    header('Location: admin_employees.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_service'])) {
    $employee_id = $_POST['employee_id'] ?? '';
    $service_id = $_POST['service_id'] ?? '';
    if (!empty($employee_id) && !empty($service_id)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO employee_services (employee_id, service_id) VALUES (?, ?)");
            $stmt->execute([$employee_id, $service_id]);
            $success = 'Przypisano usługę do pracownika!';
        } catch (PDOException $e) {
            $error = 'Ta usługa jest już przypisana do tego pracownika.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_availability'])) {
    $employee_id = $_POST['employee_id'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    if (!empty($employee_id) && !empty($day_of_week) && !empty($start_time) && !empty($end_time)) {
        $stmt = $pdo->prepare("INSERT INTO employee_availability (employee_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$employee_id, $day_of_week, $start_time, $end_time]);
        $success = 'Dodano godziny dostępności pracownika!';
    } else {
        $error = 'Wszystkie pola dostępności są wymagane.';
    }
}

$employees = $pdo->query("SELECT id, name, surname, email, active FROM users WHERE role = 'employee'")->fetchAll(PDO::FETCH_ASSOC);
$services = $pdo->query("SELECT id, name FROM services WHERE active = 1")->fetchAll(PDO::FETCH_ASSOC);

$emp_services = $pdo->query("SELECT es.employee_id, es.service_id, CONCAT(u.name, ' ', u.surname) as emp_name, s.name as service_name 
                             FROM employee_services es 
                             JOIN users u ON es.employee_id = u.id 
                             JOIN services s ON es.service_id = s.id")->fetchAll(PDO::FETCH_ASSOC);

$availabilities = $pdo->query("SELECT ea.id, ea.day_of_week, ea.start_time, ea.end_time, CONCAT(u.name, ' ', u.surname) as emp_name 
                               FROM employee_availability ea 
                               JOIN users u ON ea.employee_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Pracownikami</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="padding: 20px; max-width: 1000px; margin: auto;">
    <h1>Zarządzanie Pracownikami, Usługami i Dostępnością</h1>
    <p><a href="admin_panel.php">&larr; Wróć do Panelu Admina</a></p>

    <?php if ($error): ?><p style="color: red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color: green;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1; border: 1px solid #ccc; padding: 15px; border-radius: 6px;">
            <h3>Przypisz usługę pracownikowi</h3>
            <form method="POST">
                <p><label>Pracownik:<br>
                    <select name="employee_id" required>
                        <option value="">-- wybierz pracownika --</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['name'] . ' ' . $emp['surname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label></p>
                <p><label>Usługa:<br>
                    <select name="service_id" required>
                        <option value="">-- wybierz usługę --</option>
                        <?php foreach($services as $srv): ?>
                            <option value="<?php echo $srv['id']; ?>"><?php echo htmlspecialchars($srv['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label></p>
                <p><button type="submit" name="assign_service">Przypisz usługę</button></p>
            </form>
        </div>

        <div style="flex: 1; border: 1px solid #ccc; padding: 15px; border-radius: 6px;">
            <h3>Ustaw godziny pracy</h3>
            <form method="POST">
                <p><label>Pracownik:<br>
                    <select name="employee_id" required>
                        <option value="">-- wybierz pracownika --</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['name'] . ' ' . $emp['surname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label></p>
                <p><label>Dzień tygodnia:<br>
                    <select name="day_of_week" required>
                        <option value="Monday">Poniedziałek</option>
                        <option value="Tuesday">Wtorek</option>
                        <option value="Wednesday">Środa</option>
                        <option value="Thursday">Czwartek</option>
                        <option value="Friday">Piątek</option>
                        <option value="Saturday">Sobota</option>
                        <option value="Sunday">Niedziela</option>
                    </select>
                </label></p>
                <p><label>Od godziny:<br><input type="time" name="start_time" required></label></p>
                <p><label>Do godziny:<br><input type="time" name="end_time" required></label></p>
                <p><button type="submit" name="add_availability">Zapisz godziny</button></p>
            </form>
        </div>
    </div>

    <h3>Aktualne przypisania usług</h3>
    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; margin-top: 10px; margin-bottom: 30px;">
        <tr>
            <th>Pracownik</th>
            <th>Usługa</th>
            <th>Akcja</th>
        </tr>
        <?php if (empty($emp_services)): ?>
            <tr><td colspan="3" style="text-align: center;">Brak przypisanych usług.</td></tr>
        <?php else: ?>
            <?php foreach($emp_services as $es): ?>
            <tr>
                <td><?php echo htmlspecialchars($es['emp_name']); ?></td>
                <td><?php echo htmlspecialchars($es['service_name']); ?></td>
                <td>
                    <a href="admin_employees.php?delete_emp_id=<?php echo $es['employee_id']; ?>&delete_srv_id=<?php echo $es['service_id']; ?>" onclick="return confirm('Na pewno usunąć to przypisanie?');" style="color: red; text-decoration: none; font-weight: bold;">Usuń</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <h3>Harmonogram / Dostępność pracowników</h3>
    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; margin-top: 10px;">
        <tr>
            <th>Pracownik</th>
            <th>Dzień</th>
            <th>Od</th>
            <th>Do</th>
            <th>Akcja</th>
        </tr>
        <?php if (empty($availabilities)): ?>
            <tr><td colspan="5" style="text-align: center;">Brak ustalonej dostępności.</td></tr>
        <?php else: ?>
            <?php foreach($availabilities as $av): ?>
            <tr>
                <td><?php echo htmlspecialchars($av['emp_name']); ?></td>
                <td><?php echo htmlspecialchars($av['day_of_week']); ?></td>
                <td><?php echo htmlspecialchars($av['start_time']); ?></td>
                <td><?php echo htmlspecialchars($av['end_time']); ?></td>
                <td>
                    <a href="admin_employees.php?delete_availability=<?php echo $av['id']; ?>" onclick="return confirm('Na pewno usunąć te godziny?');" style="color: red; text-decoration: none; font-weight: bold;">Usuń</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</body>
</html>