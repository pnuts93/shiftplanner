<?php

define('EXPERIENCED_THRESHOLD', 5);

function custom_assert($actual, $expected)
{
    $result = assert($actual === $expected, "Expected $expected, got $actual");
}

function prepare($conn, $sql_file, $params = [])
{
    $sql = file_get_contents($sql_file);
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function prepare_multi_query($conn, $sql_file)
{
    $sql = file_get_contents($sql_file);
    foreach (explode(";", $sql) as $query) {
        $trimmed_query = trim($query);
        if (!empty($trimmed_query)) {
            $conn->exec($trimmed_query);
        }
    }
}

function execute($conn, int $user_id, string $date, int $shift_id): int
{
    $sql = file_get_contents(__DIR__ . '/../../api/shift/upsert-assignment.sql');
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':shift_date' => $date,
        ':shift_id' => $shift_id,
        ':experienced_years_threshold' => EXPERIENCED_THRESHOLD . ' years'
    ]);
    return $stmt->rowCount();
}
