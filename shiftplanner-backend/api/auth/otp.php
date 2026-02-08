<?php
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../email/send-email.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
init_session($config, false);
verify_method(array('POST'));
header('Content-Type: application/json');

// Read and decode JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email'], $data['token_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Sanitize and extract input
$email = trim($data['email']);
$token_type = trim($data['token_type']);

// Validate input data
if (!is_valid_email($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

// Validate user credentials
try {
    $stmt = $conn->prepare("SELECT * FROM users INNER JOIN approved_users ON users.email=approved_users.email WHERE users.email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        error_log("No user found with email: " . $email . ". Reporting suspicious activity for IP: " . $_SERVER['REMOTE_ADDR']);
        $stmt = $conn->prepare("SELECT * FROM suspicious_activities WHERE remote_ip = :ip_address AND forwarded_for = :forwarded_for AND activity_type = 'otp_request'");
        $stmt->execute([':ip_address' => $_SERVER['REMOTE_ADDR'], ':forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown']);
        if ($stmt->rowCount() > 10) {
            add_to_blacklist($_SERVER['REMOTE_ADDR']);
            error_log("IP " . $_SERVER['REMOTE_ADDR'] . " added to blacklist due to excessive OTP requests.");
        } else {
            $stmt = $conn->prepare("INSERT INTO suspicious_activities (id, remote_ip, forwarded_for, activity_type) VALUES (:uuid, :ip_address, :forwarded_for, 'otp_request')");
            $stmt->execute([':uuid' => uuidv4(), ':ip_address' => $_SERVER['REMOTE_ADDR'], ':forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown']);
        }
        http_response_code(200);
        exit;
    }
    $otp = create_otp($conn, $user['id'], $token_type);
    if (!$otp) {
        error_log("OTP request denied for user " . $user['id'] . " with token type " . $token_type . " because an active OTP already exists.");
        http_response_code(200);
        exit;
    }
    error_log("Created OTP for user " . $user['id'] . " with token type " . $token_type);
    if ($token_type === 'password_reset') {
        prepare_forgot_password($otp, $email, $config);
    } else {
        prepare_email_confirmation($otp, $email, $user['fname'], $config);
    }
    http_response_code(200);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
    exit;
}
