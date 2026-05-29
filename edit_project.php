<?php
require 'db.php';

// Проверяем, передан ли ID проекта
if (empty($_GET['id'])) {
    die("Критическая ошибка: Не указан ID проекта.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $name = $_POST['name'];
    $organization_id = $_POST['organization_id'];
    $price = $_POST['price'];
    $status_id = $_POST['status_id'];
    $employees = $_POST['employees'] ?? []; // Массив новых ID сотрудников

    try {
        // Стартуем транзакцию, чтобы изменения выполнились единым блоком
        $pdo->beginTransaction();

        // 1. Обновляем основные данные проекта
        $stmt = $pdo->prepare("UPDATE projects SET name = ?, organization_id = ?, price = ?, status_id = ? WHERE id = ?");
        $stmt->execute([$name, $organization_id, $price, $status_id, $id]);

        // 2. Удаляем все старые связи сотрудников с этим проектом
        $stmt_del = $pdo->prepare("DELETE FROM projects_employees WHERE project_id = ?");
        $stmt_del->execute([$id]);

        // 3. Записываем новые связи сотрудников с проектом
        if (!empty($employees)) {
            $stmt_ins = $pdo->prepare("INSERT INTO projects_employees (project_id, employee_id) VALUES (?, ?)");
            foreach ($employees as $emp_id) {
                $stmt_ins->execute([$id, $emp_id]);
            }
        }

        // Подтверждаем транзакцию
        $pdo->commit();

    } catch (Exception $e) {
        // Если что-то пошло не так, откатываем базу к исходному состоянию
        $pdo->rollBack();
        die("Ошибка при обновлении данных: " . $e->getMessage());
    }

    // Возвращаем пользователя на главную страницу после успешного редактирования
    header("Location: index.php");
    exit;
}

$project_id = (int)$_GET['id'];

// 1. Получаем текущие данные проекта
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    die("Ошибка: Проект с таким ID не найден.");
}

// 2. Получаем ID всех сотрудников, которые УЖЕ привязаны к этому проекту
$stmt_current_emp = $pdo->prepare("SELECT employee_id FROM projects_employees WHERE project_id = ?");
$stmt_current_emp->execute([$project_id]);
$current_employees = $stmt_current_emp->fetchAll(PDO::FETCH_COLUMN); // Возвращает плоский массив с ID

// 3. Получаем списки для выпадающих меню из других таблиц
$orgs = $pdo->query("SELECT * FROM organizations")->fetchAll();
$statuses = $pdo->query("SELECT * FROM statuses")->fetchAll();
$employees = $pdo->query("SELECT * FROM employees")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать проект</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f9; }
        .form-container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 400px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 10px 15px; background: #0056b3; color: white; border: none; cursor: pointer; border-radius: 4px; width: 100%; font-size: 16px; }
        .btn:hover { background: #004085; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Редактирование проекта №<?= $project['id'] ?></h2>

    <!-- Отправляем данные в обработчик update_project.php -->
    <form method="POST">
        <!-- Скрытое поле, чтобы передать ID проекта -->
        <input type="hidden" name="id" value="<?= $project['id'] ?>">

        <div class="form-group">
            <label>Название проекта:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($project['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Организация-заказчик:</label>
            <select name="organization_id" required>
                <?php foreach ($orgs as $org): ?>
                    <option value="<?= $org['id'] ?>" <?= $org['id'] == $project['organization_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($org['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Стоимость договора (руб):</label>
            <input type="number" step="0.01" name="price" value="<?= $project['price'] ?>" required>
        </div>

        <div class="form-group">
            <label>Статус:</label>
            <select name="status_id" required>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= $status['id'] ?>" <?= $status['id'] == $project['status_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Разработчики (Зажмите Ctrl для выбора нескольких):</label>
            <select name="employees[]" multiple style="height: 120px;">
                <?php foreach ($employees as $emp): ?>
                    <!-- Проверяем, назначен ли уже сотрудник на этот проект -->
                    <option value="<?= $emp['id'] ?>" <?= in_array($emp['id'], $current_employees) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn">Сохранить изменения</button>
        <a href="index.php" class="back-link">Отмена</a>
    </form>
</div>
</body>
</html>
