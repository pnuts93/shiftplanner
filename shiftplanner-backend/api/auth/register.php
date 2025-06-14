<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

cors();
verify_method('POST');
header('Content-Type: application/json');

// Read and decode JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email'], $data['password'], $data['fname'], $data['lname'], $data['employmentDate'], $data['hasSpecialization'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Sanitize and extract input
$email = trim($data['email']);
$password = $data['password'];
$fname = trim($data['fname']);
$lname = trim($data['lname']);
$employment_date = $data['employmentDate'];
$has_specialization = $data['hasSpecialization'];

// Validate input data
if (!is_valid_email($email) || !is_valid_password($password) || !is_allowed_length($fname, 1, 255) || !is_allowed_length($lname, 1, 255) || !is_valid_employment_date($employment_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check for allowed user emails
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM approved_users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Email not allowed']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    exit;
}

try {
    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (email, password, fname, lname, employment_date, has_specialization)
        VALUES (:email, :password, :fname, :lname, :employment_date, :has_specialization)
    ");
    $stmt->execute([
        ':email' => $email,
        ':password' => $hashedPassword,
        ':fname' => $fname,
        ':lname' => $lname,
        ':employment_date' => $employment_date,
        ':has_specialization' => string_to_bool($has_specialization),
    ]);

    http_response_code(201);
    echo json_encode(['message' => 'User registered successfully']);
} catch (PDOException $e) {
    if ($e->getCode() === '23505') { // Unique violation
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
}
