<?php

// Generate JWT
function generate_jwt($user_id, $email, $role, $expiration_time = 3600)
{
    $secret_key = getenv('JWT_SECRET_KEY');
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $user_id,
        'email' => $email,
        'role' => $role,
        'exp' => time() + $expiration_time
    ]);
    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payload);
    $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $secret_key, true);
    $base64UrlSignature = base64UrlEncode($signature);
    return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
}

function base64UrlEncode($text)
{
    return str_replace(
        ['+', '/', '='],
        ['-', '_', ''],
        base64_encode($text)
    );
}

// Validate JWT
function validate_jwt($jwt)
{
    $secret_key = getenv('JWT_SECRET_KEY');
    $parts = explode('.', substr($jwt, strlen("Bearer ")));
    if (count($parts) !== 3) {
        return null;
    }
    list($header, $payload, $signature) = $parts;
    $expected_signature = hash_hmac('sha256', "$header.$payload", $secret_key, true);
    if (base64UrlEncode($expected_signature) !== $signature) {
        return null;
    }
    $payload_data = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', $payload))), true);
    if (isset($payload_data['exp']) && $payload_data['exp'] < time()) {
        return null;
    }
    return $payload_data;
}

function verify_method(array $allowed_methods) {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, $allowed_methods)) {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    return $method;
}

function authenticate() {
    $token = $_SERVER['HTTP_AUTHORIZATION'];
    if ($token === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $token_payload = validate_jwt($token);
    if ($token_payload === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    return $token_payload;
}