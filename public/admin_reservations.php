<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $res_id = $_POST['reservation_id'] ?? '';
    $new_status = $_POST['status'] ?? '';
    
    $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_statuses) && !empty($res_id)) {
        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $res_id]);
    }
}


$employees = $pdo->query("SELECT id, name, surname FROM users WHERE role = 'employee'")->fetchAll(PDO::FETCH_ASSOC);


$filter_status = $_GET['status'] ?? '';
$filter_employee = $_GET['employee_id'] ?? '';
$filter_date = $_GET['date'] ?? '';

$sql = "SELECT r.*, u.name as client_name, u.surname as client_surname, 
               e.name as emp_name, e.surname as emp_surname, s.name as service_name 
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN users e ON r.employee_id = e.id
        JOIN services s ON r.service_id = s.id
        WHERE 1=1";

$params = [];

if (!empty($filter_status)) {
    $sql .= " AND r.status = ?";
    $params[] = $filter_status;
}
if (!empty($filter_employee)) {
    $sql .= " AND r.employee_id = ?";
    $params[] = $filter_employee;
}
if (!empty($filter_date)) {
    $sql .= " AND r.reservation_date = ?";
    $params[] = $filter_date;
}

$sql .= " ORDER BY r.reservation_date DESC, r.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Rezerwacjami</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="padding: 20px; max-width: 1100px; margin: auto;">
    <h1>Zarządzanie i Filtrowanie Rezerwacji</h1>
    <p><a href="admin_panel.php">&larr; Wróć do Panelu Admina</a></p>

    <form method="GET" style="background: #333; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end;">
        <div>
            <label>Status:<br>
                <select name="status">
                    <option value="">-- wszystkie --</option>
                    <option value="pending" <?php if($filter_status=='pending') echo 'selected'; ?>>pending</option>
                    <option value="confirmed" <?php if($filter_status=='confirmed') echo 'selected'; ?>>confirmed</option>
                    <option value="completed" <?php if($filter_status=='completed') echo 'selected'; ?>>completed</option>
                    <option value="cancelled" <?php if($filter_status=='cancelled') echo 'selected'; ?>>cancelled</option>
                </select>
            </label>
        </div>
        <div>
            <label>Pracownik:<br>
                <select name="employee_id">
                    <option value="">-- wszyscy --</option>
                    <?php foreach($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php if($filter_employee == $emp['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($emp['name'] . ' ' . $emp['surname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div>
            <label>Data:<br>
                <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
            </label>
        </div>
        <div>
            <button type="submit">Filtruj</button>
            <a href="admin_reservations.php" style="margin-left: 10px;">Resetuj</a>
        </div>
    </form>

    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%;">
        <tr>
            <th>ID</th>
            <th>Klient</th>
            <th>Pracownik</th>
            <th>Usługa</th>
            <th>Data i Godzina</th>
            <th>Status</th>
            <th>Akcja</th>
        </tr>
        <?php if (empty($reservations)): ?>
            <tr><td colspan="7" style="text-align: center;">Brak rezerwacji spełniających kryteria.</td></tr>
        <?php else: ?>
            <?php foreach($reservations as $res): ?>
            <tr>
                <td><?php echo $res['id']; ?></td>
                <td><?php echo htmlspecialchars($res['client_name'] . ' ' . $res['client_surname']); ?></td>
                <td><?php echo htmlspecialchars($res['emp_name'] . ' ' . $res['emp_surname']); ?></td>
                <td><?php echo htmlspecialchars($res['service_name']); ?></td>
                <td><?php echo $res['reservation_date'] . '<br>' . $res['start_time'] . ' - ' . $res['end_time']; ?></td>
                <td><b><?php echo $res['status']; ?></b></td>
                <td>
                    <form method="POST" style="display: inline-flex; gap: 5px;">
                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                        <select name="status">
                            <option value="pending" <?php if($res['status']=='pending') echo 'selected'; ?>>pending</option>
                            <option value="confirmed" <?php if($res['status']=='confirmed') echo 'selected'; ?>>confirmed</option>
                            <option value="completed" <?php if($res['status']=='completed') echo 'selected'; ?>>completed</option>
                            <option value="cancelled" <?php if($res['status']=='cancelled') echo 'selected'; ?>>cancelled</option>
                        </select>
                        <button type="submit" name="update_status">Zmień</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</body>
</html>