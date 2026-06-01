<?php
require 'db.php';

// Запрос для вывода проектов с названиями организаций, статусов и списком сотрудников через GROUP_CONCAT
$stmt = $pdo->query("
    SELECT 
        p.id, 
        p.name AS project_name, 
        o.name AS org_name, 
        p.price, 
        s.name AS status_name,
        GROUP_CONCAT(e.name SEPARATOR ', ') AS developers
    FROM projects p
    LEFT JOIN organizations o ON p.organization_id = o.id
    LEFT JOIN statuses s ON p.status_id = s.id
    LEFT JOIN projects_employees pe ON p.id = pe.project_id
    LEFT JOIN employees e ON pe.employee_id = e.id
    GROUP BY p.id
");
$projects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>МНВП Квадро — Проекты</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f9; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #0056b3; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
<h1>Текущие проекты МНВП "Квадро"</h1>
<a href="add_project.php" class="btn">+ Добавить новый проект</a>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Название проекта</th>
        <th>Организация-заказчик</th>
        <th>Стоимость (руб.)</th>
        <th>Статус</th>
        <th>Разработчики</th>
        <th>Действия</th> <!-- Добавили заголовок -->
    </tr>
    </thead>
    <tbody>
    <?php foreach ($projects as $project): ?>
        <tr>
            <td><?= $project['id'] ?></td>
            <td><strong><?= htmlspecialchars($project['project_name']) ?></strong></td>
            <td><?= htmlspecialchars($project['org_name'] ?? 'Не указана') ?></td>
            <td><?= number_format($project['price'], 2, ',', ' ') ?></td>
            <td><?= htmlspecialchars($project['status_name'] ?? 'Нет статуса') ?></td>
            <td><?= htmlspecialchars($project['developers'] ?? 'Не назначены') ?></td>
            <!-- Добавили кнопку редактирования с ID проекта в параметрах -->
            <td>
                <a href="edit_project.php?id=<?= $project['id'] ?>" style="color: #0056b3; text-decoration: none; font-weight: bold;">✏️ Редактировать</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
