<?php

function verify_method(array $allowed_methods)
{
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, $allowed_methods)) {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    return $method;
}

function create_otp($conn, $user_id, $token_type)
{
    $otp = bin2hex(random_bytes(16));
    $stmt = $conn->prepare("INSERT INTO one_time_tokens (token, user_id, expires_at, token_type) VALUES (:otp, :user_id, NOW() + INTERVAL '30 MINUTES', :token_type)");
    $stmt->execute([':otp' => $otp, ':user_id' => $user_id, ':token_type' => $token_type]);
    return $otp;
}

function get_login_attempts($conn, $email)
{
    $stmt = $conn->prepare("SELECT login_attempts FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    return $stmt->fetchColumn();
}

function increase_login_attempts($conn, $user_id)
{
    $stmt = $conn->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = :user_id RETURNING login_attempts");
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchColumn();
}

function reset_login_attempts($conn, $user_id)
{
    $stmt = $conn->prepare("UPDATE users SET login_attempts = 0 WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
}

function uuidv4()
{
    $data = random_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
