<?php
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../email/send-email.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
verify_method(array('POST'));
header('Content-Type: application/json');
init_session($config, false);

// Read and decode JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Sanitize and extract input
$email = trim($data['email']);
$password = $data['password'];

// Validate input data
if (!is_valid_email($email) || !is_valid_password($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

// Validate user credentials
try {
    $login_attempts = get_login_attempts($conn, $email);
    if ($login_attempts >= $config['MAX_LOGIN_ATTEMPTS']) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many login attempts. Please unlock account.']);
        exit;
    }
    $stmt = $conn->prepare("SELECT * FROM users INNER JOIN approved_users ON users.email=approved_users.email WHERE users.email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }
    if (!password_verify($password, $user['password'])) {
        if ($login_attempts + 1 >= $config['MAX_LOGIN_ATTEMPTS']) {
            error_log("Too many login attempts for user {$user['id']} from REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . " HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
            $uuid = uuidv4();
            $stmt = $conn->prepare("INSERT INTO suspicious_activities (id, remote_ip, forwarded_for, activity_type, user_id) VALUES (:id, :remote_ip, :forwarded_for, :activity_type, :user_id)");
            $stmt->execute([
                ':id' => $uuid,
                ':remote_ip' => $_SERVER['REMOTE_ADDR'],
                ':forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
                ':activity_type' => 'too_many_login_attempts',
                ':user_id' => $user['id']
            ]);
            increase_login_attempts($conn, $user['id']);
            prepare_max_retries($email, $config, $uuid);
            http_response_code(429);
            echo json_encode(['error' => 'Too many login attempts. Please try again later.']);
            exit;
        }
        increase_login_attempts($conn, $user['id']);
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }
    if (!$user['is_email_confirmed']) {
        http_response_code(403);
        echo json_encode(['error' => 'Email not confirmed']);
        exit;
    }
    reset_login_attempts($conn, $user['id']);
    session_regenerate_id(true);
    $role = $user['is_admin'] ? 'admin' : 'user';
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $role;
    $_SESSION['email'] = $email;
    $token = bin2hex(random_bytes(32));
    $_SESSION['token'] = $token;
    http_response_code(200);
    echo json_encode([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $email,
            'fname' => $user['fname'],
            'lname' => $user['lname'],
            'employmentDate' => $user['employment_date'],
            'hasSpecialization' => $user['has_specialization'],
            'locale' => $user['locale'],
            'role' => $role
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
    exit;
}
