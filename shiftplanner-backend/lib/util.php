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
        "https://{$config['DOMAIN']}"
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
