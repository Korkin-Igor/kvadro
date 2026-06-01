<?php

function isRowUnique(array $targetRow, \PDO $pdo, string $table = 'projects'): bool
{
    $whereParts = [];
    foreach (array_keys($targetRow) as $column) {
        $cleanColumn = str_replace('`', '', $column);
        $whereParts[] = "`$cleanColumn` = ?";
    }

    $whereString = implode(' AND ', $whereParts);

    $cleanTable = str_replace('`', '', $table);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$cleanTable` WHERE $whereString");

    $stmt->execute(array_values($targetRow));

    $count = (int) $stmt->fetchColumn(); // число совпадений

    // Если count = 0, значит строка уникальна
    return $count == 0;
}
