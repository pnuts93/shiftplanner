<?php
$host = 'localhost';
$dbname = 'shiftplanner';
$user = 'postgres';
$pass = 'postgres';
$port = '5432';

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    // Set error reporting
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die("PostgreSQL connection failed: " . $e->getMessage());
}
