<?php
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
verify_method(array('GET'));
init_session($config);
error_log("config holds session info" . implode(", ", array_keys($config, "session*")));
// Fetch data from Database
try {
    $stmt = $conn->prepare("SELECT users.id, users.email, users.fname, users.lname, users.employment_date, users.has_specialization, users.locale, approved_users.is_admin, approved_users.is_counted FROM users INNER JOIN approved_users ON users.email=approved_users.email WHERE users.id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    $role = $user['is_admin'] ? 'admin' : 'user';
    http_response_code(200);
    echo json_encode([
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'fname' => $user['fname'],
            'lname' => $user['lname'],
            'employmentDate' => $user['employment_date'],
            'hasSpecialization' => $user['has_specialization'],
            'locale' => $user['locale'],
            'role' => $role,
            'isCounted' => boolval($user['is_counted'])
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
    exit;
}
