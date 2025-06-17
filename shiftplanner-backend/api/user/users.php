<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

cors();
$method = verify_method(array('GET', 'POST', 'PUT'));
$payload = authenticate();

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
    try {
        $stmt = $conn->prepare("SELECT * FROM users");
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
    header('Content-Type: application/json');

    // Read and decode JSON body
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['email'], $data['isAdmin'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $email = trim($data['email']);

    if (!is_valid_email($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO users (email, is_admin) VALUES (:email, :is_admin)");
        $stmt->execute([':email' => $email, ':is_admin' => strlen($data['isAdmin']) === 0 ? 0 : 1]);
        http_response_code(201);
        echo json_encode(['message' => 'User added successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log($e->getMessage());
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
}

function update_user()
{
    global $conn;
    global $payload;
    header('Content-Type: application/json');

    // Read and decode JSON body
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['email'], $data['isAdmin'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $email = trim($data['email']);
    $is_admin = boolval($data['isAdmin']);

    if (!is_valid_email($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    } else if (!$is_admin && $payload['email'] !== $email) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot remove admin privileges from other users']);
        exit;
    }

    try {
        // Check if user is only admin and tries to remove own admin privileges
        if ($payload['email'] === $email && !$is_admin) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE is_admin = TRUE");
            $stmt->execute();
            if ($stmt->fetchColumn() === 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot remove own admin privileges, at least one admin is required']);
                exit;
            }
        }
        $stmt = $conn->prepare("UPDATE users SET is_admin = :is_admin WHERE email = :email");
        $stmt->execute([':email' => $email, ':is_admin' => $is_admin]);
        http_response_code(200);
        echo json_encode(['message' => 'User updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
}
