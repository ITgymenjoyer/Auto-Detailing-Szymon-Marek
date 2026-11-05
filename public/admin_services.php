<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config/db.php';


if (isset($_GET['delete_category'])) {
    $cat_id = (int)$_GET['delete_category'];
    $stmt = $pdo->prepare("DELETE FROM service_categories WHERE id = ?");
    $stmt->execute([$cat_id]);
    header('Location: admin_services.php');
    exit;
}


if (isset($_GET['delete_service'])) {
    $srv_id = (int)$_GET['delete_service'];
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$srv_id]);
    header('Location: admin_services.php');
    exit;
}

$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $cat_name = trim($_POST['cat_name'] ?? '');
    $cat_desc = trim($_POST['cat_desc'] ?? '');
    if (!empty($cat_name)) {
        $stmt = $pdo->prepare("INSERT INTO service_categories (name, description) VALUES (?, ?)");
        $stmt->execute([$cat_name, $cat_desc]);
        $success = 'Dodano nową kategorię!';
    } else {
        $error = 'Nazwa kategorii jest wymagana.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $category_id = $_POST['category_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration'] ?? 0);
    $price = $_POST['price'] ?? '';

    if (!empty($category_id) && !empty($name) && $duration > 0 && !empty($price)) {
        $stmt = $pdo->prepare("INSERT INTO services (category_id, name, description, duration, price, active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$category_id, $name, $description, $duration, $price]);
        $success = 'Dodano nową usługę!';
    } else {
        $error = 'Wszystkie pola usługi są wymagane, a czas trwania musi być większy od 0.';
    }
}

$categories = $pdo->query("SELECT * FROM service_categories")->fetchAll(PDO::FETCH_ASSOC);
$services = $pdo->query("SELECT s.*, c.name as cat_name FROM services s JOIN service_categories c ON s.category_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Usługami i Kategoriami</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="padding: 20px; max-width: 1000px; margin: auto;">
    <h1>Zarządzanie Usługami i Kategoriami</h1>
    <p><a href="admin_panel.php">&larr; Wróć do Panelu Admina</a></p>

    <?php if ($error): ?><p style="color: red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color: green;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
    <div style="display: flex; gap: 30px;">
        <div style="flex: 1; border: 1px solid #ccc; padding: 15px; border-radius: 6px;">
            <h3>Dodaj Kategorię</h3>
            <form method="POST">
                <p><label>Nazwa kategorii:<br><input type="text" name="cat_name" required></label></p>
                <p><label>Opis:<br><textarea name="cat_desc"></textarea></label></p>
                <p><button type="submit" name="add_category">Dodaj kategorię</button></p>
            </form>
        </div>

        <div style="flex: 1; border: 1px solid #ccc; padding: 15px; border-radius: 6px;">
            <h3>Dodaj Usługę</h3>
            <form method="POST">
                <p><label>Kategoria:<br>
                    <select name="category_id" required>
                        <option value="">-- wybierz kategorię --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label></p>
                <p><label>Nazwa usługi:<br><input type="text" name="name" required></label></p>
                <p><label>Opis:<br><textarea name="description"></textarea></label></p>
                <p><label>Czas trwania (w minutach, np. 180):<br><input type="number" min="1" name="duration" placeholder="np. 60" required></label></p>
                <p><label>Cena (PLN):<br><input type="number" step="0.01" name="price" required></label></p>
                <p><button type="submit" name="add_service">Dodaj usługę</button></p>
            </form>
        </div>
    </div>

    <!-- Tabela kategorii -->
    <h3>Lista kategorii w systemie</h3>
    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; margin-top: 10px; margin-bottom: 30px;">
        <tr>
            <th>ID</th>
            <th>Nazwa</th>
            <th>Opis</th>
            <th>Akcja</th>
        </tr>
        <?php if (empty($categories)): ?>
            <tr><td colspan="4" style="text-align: center;">Brak kategorii.</td></tr>
        <?php else: ?>
            <?php foreach($categories as $cat): ?>
            <tr>
                <td><?php echo $cat['id']; ?></td>
                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                <td><?php echo htmlspecialchars($cat['description']); ?></td>
                <td>
                    <a href="admin_services.php?delete_category=<?php echo $cat['id']; ?>" onclick="return confirm('Na pewno usunąć tę kategorię?');" style="color: red; text-decoration: none; font-weight: bold;">Usuń</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- Tabela usług -->
    <h3>Lista usług w systemie</h3>
    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; margin-top: 10px;">
        <tr>
            <th>ID</th>
            <th>Kategoria</th>
            <th>Nazwa</th>
            <th>Cena</th>
            <th>Czas trwania</th>
            <th>Status</th>
            <th>Akcja</th>
        </tr>
        <?php if (empty($services)): ?>
            <tr><td colspan="7" style="text-align: center;">Brak usług.</td></tr>
        <?php else: ?>
            <?php foreach($services as $srv): ?>
            <tr>
                <td><?php echo $srv['id']; ?></td>
                <td><?php echo htmlspecialchars($srv['cat_name']); ?></td>
                <td><?php echo htmlspecialchars($srv['name']); ?></td>
                <td><?php echo $srv['price']; ?> zł</td>
                <td><?php echo $srv['duration']; ?> minut</td>
                <td><?php echo $srv['active'] ? 'Aktywna' : 'Nieaktywna'; ?></td>
                <td>
                    <a href="admin_services.php?delete_service=<?php echo $srv['id']; ?>" onclick="return confirm('Na pewno usunąć tę usługę?');" style="color: red; text-decoration: none; font-weight: bold;">Usuń</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</body>
</html>