<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

cors();
verify_method(array('PUT'));
$payload = authenticate();
header('Content-Type: application/json');

// Read and decode JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['fname'], $data['lname'], $data['employmentDate'], $data['hasSpecialization'], $data['locale'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Sanitize and extract input
$email = trim($payload['email']);
$fname = trim($data['fname']);
$lname = trim($data['lname']);
$employment_date = $data['employmentDate'];
$has_specialization = $data['hasSpecialization'];
$locale = $data['locale'];

// Validate input data
if (!is_valid_email($email) || !is_allowed_length($fname, 1, 255) || !is_allowed_length($lname, 1, 255) || !is_valid_employment_date($employment_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

if (isset($data['oldPassword']) && $data['oldPassword'] !== '' && $data['newPassword'] !== '' && $data['confirmPassword'] !== '') {
    if (!isset($data['newPassword'], $data['confirmPassword']) || !is_valid_password($data['newPassword']) || !is_valid_password($data['confirmPassword']) || $data['newPassword'] !== $data['confirmPassword']) {
        http_response_code(400);
        echo json_encode(['error' => 'New password is not valid']);
        exit;
    }
    $oldPassword = $data['oldPassword'];
    $newPassword = $data['newPassword'];
    $confirmPassword = $data['confirmPassword'];
    // Hash the password
    $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    // Validate old password
    try {
        $stmt = $conn->prepare("SELECT password FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid old password']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
        exit;
    }
}

// Check for allowed user emails
try {
    if (isset($hashedNewPassword)) {
        $stmt = $conn->prepare("UPDATE users SET fname = :fname, lname = :lname, employment_date = :employment_date, has_specialization = :has_specialization, password = :password, locale = :locale WHERE email = :email");
        $stmt->execute([
            ':email' => $email,
            ':fname' => $fname,
            ':lname' => $lname,
            ':employment_date' => $employment_date,
            ':has_specialization' => $has_specialization,
            ':password' => $hashedNewPassword,
            ':locale' => $locale
        ]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET fname = :fname, lname = :lname, employment_date = :employment_date, has_specialization = :has_specialization, locale = :locale WHERE email = :email");
        $stmt->execute([
            ':email' => $email,
            ':fname' => $fname,
            ':lname' => $lname,
            ':employment_date' => $employment_date,
            ':has_specialization' => $has_specialization,
            ':locale' => $locale
        ]);
        http_response_code(200);
        echo json_encode(['message' => 'Profile updated successfully']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    exit;
}
