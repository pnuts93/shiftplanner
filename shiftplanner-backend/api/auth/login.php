<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
verify_method(array('POST'));
header('Content-Type: application/json');

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
    $stmt = $conn->prepare("SELECT * FROM users INNER JOIN approved_users ON users.email=approved_users.email WHERE users.email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    } else if (!$user['is_email_confirmed']) {
        http_response_code(403);
        echo json_encode(['error' => 'Email not confirmed']);
        exit;
    }
    $role = $user['is_admin'] ? 'admin' : 'user';
    $token = generate_jwt($user['id'], $email, $role, $config);
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
