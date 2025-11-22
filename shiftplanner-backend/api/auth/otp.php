<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../email/send-email.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
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
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email']);
        exit;
    }
    $otp = create_otp($conn, $user['id'], $token_type);
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
