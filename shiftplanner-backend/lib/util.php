<?php

function is_valid_email($email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_allowed_length($email, 3, 255)) {
        return false;
    }
    return true;
}

function is_valid_password($password)
{
    if (!is_allowed_length($password, 8, 255)) {
        return false;
    }
    return true;
}

function is_allowed_length($string, $min_length, $max_length)
{
    if (strlen($string) < $min_length || strlen($string) > $max_length) {
        return false;
    }
    return true;
}

function is_valid_employment_date($date)
{
    $timestamp = strtotime($date);
    if ($timestamp === false || $timestamp > time()) {
        return false;
    }
    return true;
}

function string_to_bool($string)
{
    if (is_bool($string)) {
        return $string;
    }
    if (is_string($string)) {
        $lowered = strtolower($string);
        return in_array($lowered, ['true', '1', 'yes', 'on'], true);
    }
    return false;
}

function cors($config)
{
    $allowed_origins = [
        'http://localhost:4200', // Local development
        'http://127.0.0.1:4200', // Local development
        "https://{$config['DOMAIN']}",
        "https://www.{$config['DOMAIN']}"
    ];
    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
}

function init_session(array $options, bool $isAuthNeeded = true)
{
    if (is_in_blacklist($_SERVER['REMOTE_ADDR'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    session_start(filter_session_options($options));
    if (!$isAuthNeeded) {
        return;
    }
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

function filter_session_options(array $config): array
{
    $arr = [];
    foreach ($config as $key => $value) {
        if (str_starts_with($key, 'session.')) {
            $optionKey = substr($key, strlen('session.'));
            $arr[$optionKey] = $value;
        }
    }
    return $arr;
}

function is_in_blacklist($ip)
{
    $blacklist = file_get_contents(__DIR__ . '/../private/blacklist.txt');
    $blacklist_array = array_map('trim', explode("\n", $blacklist));
    return in_array($ip, $blacklist_array);
}

function add_to_blacklist($ip)
{
    $blacklist_file = __DIR__ . '/../private/blacklist.txt';
    if (!is_in_blacklist($ip)) {
        file_put_contents($blacklist_file, $ip . PHP_EOL, FILE_APPEND);
    }
}

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
        error_log("Database connection failed: " . $e->getMessage());
        http_response_code(500);
        die("Database connection failed");
    }
}
