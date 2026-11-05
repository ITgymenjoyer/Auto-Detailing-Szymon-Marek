<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config/db.php';

if (isset($_GET['toggle_active']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $current_status = (int)$_GET['status'];
    $new_status = $current_status === 1 ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE users SET active = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    header('Location: admin_users.php');
    exit;
}

$users = $pdo->query("SELECT id, name, surname, email, phone, role, active, created_at FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Użytkownikami</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="padding: 20px; max-width: 1100px; margin: auto;">
    <h1>Zarządzanie Użytkownikami Systemu</h1>
    <p><a href="admin_panel.php">&larr; Wróć do Panelu Admina</a></p>

    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; margin-top: 15px;">
        <tr>
            <th>ID</th>
            <th>Imię i Nazwisko</th>
            <th>E-mail</th>
            <th>Telefon</th>
            <th>Rola</th>
            <th>Status</th>
            <th>Data rejestracji</th>
            <th>Akcja</th>
        </tr>
        <?php foreach($users as $u): ?>
        <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['name'] . ' ' . $u['surname']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['phone']); ?></td>
            <td><b><?php echo htmlspecialchars($u['role']); ?></b></td>
            <td><?php echo $u['active'] ? 'Aktywny' : 'Zablokowany'; ?></td>
            <td><?php echo $u['created_at']; ?></td>
            <td>
                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                    <a href="admin_users.php?toggle_active=1&id=<?php echo $u['id']; ?>&status=<?php echo $u['active']; ?>" 
                       onclick="return confirm('Na pewno zmienić status tego użytkownika?');" 
                       style="color: <?php echo $u['active'] ? 'red' : 'green'; ?>; text-decoration: none; font-weight: bold;">
                       <?php echo $u['active'] ? 'Zablokuj' : 'Aktywuj'; ?>
                    </a>
                <?php else: ?>
                    <span>(Twoje konto)</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>