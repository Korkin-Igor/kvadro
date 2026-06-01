<?php
require 'db.php';
require 'validator.php';

$error = '';
$test = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $organization_id = $_POST['organization_id'];
    $price = $_POST['price'];
    $status_id = $_POST['status_id'];
    $employees = $_POST['employees'] ?? []; // Массив ID выбранных сотрудников

    $row = [
        'name' => $name,
        'organization_id' => $organization_id,
        'price' => $price,
        'status_id' => $status_id
    ];
    if (!isRowUnique(targetRow: $row, pdo: $pdo)) {
        $error = 'Ошибка! Такая запись уже существует!';
    } else {
        // 1. Вставляем проект
        $stmt = $pdo->prepare("INSERT INTO projects (name, organization_id, price, status_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $organization_id, $price, $status_id]);
        $project_id = $pdo->lastInsertId();

        // 2. Связываем с сотрудниками в проекты_сотрудники
        if (!empty($employees)) {
            $stmtLink = $pdo->prepare("INSERT INTO projects_employees (project_id, employee_id) VALUES (?, ?)");
            foreach ($employees as $emp_id) {
                $stmtLink->execute([$project_id, $emp_id]);
            }
        }

        header("Location: index.php");
        exit;

    }
}

// Получаем данные для выпадающих списков
$orgs = $pdo->query("SELECT * FROM organizations")->fetchAll();
$statuses = $pdo->query("SELECT * FROM statuses")->fetchAll();
$employees = $pdo->query("SELECT * FROM employees")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить проект</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .form-group { margin-bottom: 15px; width: 300px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 10px 15px; background: #0056b3; color: white; border: none; cursor: pointer; }
        .checkbox-group label { font-weight: normal; display: flex; align-items: center; gap: 5px; }
        .danger {font-size: 20px; color: red}
    </style>
</head>
<body>
<h1>Новый проект</h1>
<?php if (!empty($error)): ?>
<p class="danger"><?=$error?></p>
<?php endif;?>
<form method="POST">
    <div class="form-group">
        <label>Название проекта:</label>
        <input type="text" name="name" required>
    </div>
    <div class="form-group">
        <label>Организация:</label>
        <select name="organization_id" required>
            <?php foreach ($orgs as $org): ?>
                <option value="<?= $org['id'] ?>"><?= htmlspecialchars($org['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Стоимость (руб):</label>
        <input type="number" step="0.01" name="price" required>
    </div>
    <div class="form-group">
        <label>Статус:</label>
        <select name="status_id" required>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= $status['id'] ?>"><?= htmlspecialchars($status['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Назначить разработчиков (зажмите Ctrl для выбора нескольких):</label>
        <select name="employees[]" multiple style="height: 80px;">
            <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn">Сохранить проект</button>
    <a href="index.php" style="margin-left: 10px;">Назад</a>
</form>
</body>
</html>
