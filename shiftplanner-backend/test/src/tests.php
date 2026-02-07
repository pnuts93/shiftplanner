<?php

require_once __DIR__ . '/test_assignments.php';

$conn = connect_test_db();

function connect_test_db()
{
    $host = "localhost";
    $dbname = "shiftplanner";
    $user = "postgres";
    $pass = "postgres";
    $port = 5432;

    try {
        $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
        // Set error reporting
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log("Connected to the test database successfully.");
        return $conn;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
    }
}

function prepare_db($conn)
{
    $sql_files = scandir(__DIR__ . '/../../../db_setup');
    foreach ($sql_files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            prepare_multi_query($conn, __DIR__ . '/../../../db_setup/' . $file);
        }
    }
    prepare_multi_query($conn, __DIR__ . '/../resources/init.sql');
}

function main()
{
    global $conn;
    error_log("Starting Test Suite...");
    if (ini_get('zend.assertions') !== '1') {
        error_log("Assertions are not enabled! Please enable them in your PHP configuration.");
        exit(1);
    }
    prepare_db($conn);
    run_assigment_suite($conn);
}

main();
