<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

cors();
verify_method(array('GET'));
$payload = authenticate();

// Fetch data from Database
try {
    $stmt = $conn->prepare("SELECT users.id, users.email, users.fname, users.lname, users.employment_date, users.has_specialization, users.locale, approved_users.is_admin FROM users INNER JOIN approved_users ON users.email=approved_users.email WHERE users.email = :email");
    $stmt->execute([':email' => $payload['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['id'] !== $payload['user_id']) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
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
            'role' => $role
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    exit;
}
