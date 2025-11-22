<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../email/send-email.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
$method = verify_method(array('GET', 'POST', 'PUT'));

switch ($method) {
    case 'GET':
        get_users();
        break;
    case 'POST':
        add_user();
        break;
    case 'PUT':
        update_user();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
}

function get_users()
{

    global $conn;
    global $config;
    authenticate($config);
    try {
        $stmt = $conn->prepare("SELECT * FROM users ORDER BY fname");
        $stmt->execute();
        $users = [];
        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($user) {
                $users[] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'fname' => $user['fname'],
                    'lname' => $user['lname'],
                    'employmentDate' => $user['employment_date'],
                    'hasSpecialization' => $user['has_specialization'],
                    'locale' => $user['locale']
                ];
            }
        }
        http_response_code(200);
        echo json_encode($users);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
}

function add_user()
{
    global $conn;
    global $config;
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
    $has_specialization = strlen($data['hasSpecialization']) === 0 ? 0 : 1;
    $locale = $config['DEFAULT_LOCALE'] ?? 'en';

    // Validate input data
    if (!is_valid_email($email) || !is_valid_password($password) || !is_allowed_length($fname, 1, 255) || !is_allowed_length($lname, 1, 255) || !is_valid_employment_date($employment_date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check for allowed user emails
        error_log("Attempting registration of new user");
        $stmt = $conn->prepare("SELECT COUNT(*) FROM approved_users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() == 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Email not allowed']);
            exit;
        }
        // Insert user
        $stmt = $conn->prepare("
        INSERT INTO users (email, password, fname, lname, employment_date, has_specialization, locale)
        VALUES (:email, :password, :fname, :lname, :employment_date, :has_specialization, :locale)
        RETURNING *
    ");
        $stmt->execute([
            ':email' => $email,
            ':password' => $hashedPassword,
            ':fname' => $fname,
            ':lname' => $lname,
            ':employment_date' => $employment_date,
            ':has_specialization' => $has_specialization,
            ':locale' => $locale
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("User {$user['id']} registered successfully");
        $otp = create_otp($conn, $user['id'], 'email_confirmation');
        prepare_email_confirmation($otp, $email, $fname, $config);
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
}

function update_user()
{
    global $conn;
    global $config;
    $payload = authenticate($config);
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
    $has_specialization = strlen($data['hasSpecialization']) === 0 ? 0 : 1;
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
        }
        http_response_code(200);
        echo json_encode(['message' => 'Profile updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
        exit;
    }
}
