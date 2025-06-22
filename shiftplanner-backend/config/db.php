<?php

function db($config)
{
    $host = $config['DB_HOST'];
    $dbname = $config['DB_NAME'];
    $user = $config['DB_USER'];
    $pass = $config['DB_PASS'];
    $port = $config['DB_PORT'];
    if (empty($host) || empty($dbname) || empty($user) || empty($pass) || empty($port)) {
        http_response_code(500);
        die("Database configuration is incomplete");
    }
    try {
        $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
        // Set error reporting
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        http_response_code(500);
        die("Database connection failed");
    }
}
