<?php

// Generate JWT
function generate_jwt($user_id, $email, $role, $config)
{
    $secret_key = $config['JWT_SECRET_KEY'];
    $expiration_time = isset($config['JWT_EXPIRATION_TIME']) ? intval($config['JWT_EXPIRATION_TIME']) : 3600;
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

function base64UrlDecode($input) {
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $input .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($input, '-_', '+/'));
}

// Validate JWT
function validate_jwt($config, $jwt)
{
    $secret_key = $config['JWT_SECRET_KEY'];
    if (strpos($jwt, 'Bearer ') === 0) {
        $jwt = substr($jwt, 7);
    }
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }
    list($header, $payload, $signature) = $parts;
    $expected_signature = hash_hmac('sha256', "$header.$payload", $secret_key, true);
    if (!hash_equals(base64UrlEncode($expected_signature), $signature)) {
        return null;
    }
    $payload_data = json_decode(base64UrlDecode($payload), true);
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

function authenticate($config) {
    $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;
    if ($token === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $token_payload = validate_jwt($config, $token);
    if ($token_payload === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    return $token_payload;
}

function create_otp($conn, $user_id, $token_type) {
    $otp = bin2hex(random_bytes(16));
    $stmt = $conn->prepare("INSERT INTO one_time_tokens (token, user_id, expires_at, token_type) VALUES (:otp, :user_id, NOW() + INTERVAL '30 MINUTES', :token_type)");
    $stmt->execute([':otp' => $otp, ':user_id' => $user_id, ':token_type' => $token_type]);
    return $otp;
}