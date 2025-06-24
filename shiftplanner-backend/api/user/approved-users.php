<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
$method = verify_method(array('GET', 'POST', 'PUT', 'DELETE'));
$payload = authenticate($config);

if (!$payload || $payload['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

switch ($method) {
    case 'GET':
        get_approved_users();
        break;
    case 'POST':
        add_approved_user();
        break;
    case 'PUT':
        update_approved_user();
        break;
    case 'DELETE':
        delete_approved_user();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
}

function get_approved_users()
{
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM approved_users");
        $stmt->execute();
        $users = [];
        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($user) {
                $users[] = [
                    'email' => $user['email'],
                    'isAdmin' => boolval($user['is_admin'])
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

function add_approved_user()
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

    if (!is_valid_email($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO approved_users (email, is_admin) VALUES (:email, :is_admin)");
        $stmt->execute([':email' => $email, ':is_admin' => strlen($data['isAdmin']) === 0 ? 0 : 1]);
        error_log("User {$payload['user_id']} added approved user: $email");
        http_response_code(201);
        echo json_encode(['message' => 'User added successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log($e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }
}

function update_approved_user()
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
    $is_admin = strlen($data['isAdmin']) === 0 ? 0 : 1;

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
            $stmt = $conn->prepare("SELECT COUNT(*) FROM approved_users WHERE is_admin = TRUE");
            $stmt->execute();
            if ($stmt->fetchColumn() === 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot remove own admin privileges, at least one admin is required']);
                exit;
            }
        }
        $stmt = $conn->prepare("UPDATE approved_users SET is_admin = :is_admin WHERE email = :email");
        $stmt->execute([':email' => $email, ':is_admin' => $is_admin]);
        http_response_code(200);
        echo json_encode(['message' => 'User updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
}

function delete_approved_user()
{
    global $conn;
    global $payload;
    header('Content-Type: application/json');

    // Read and decode JSON body
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['email'])) {
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
        // Check if user is only admin and tries to remove themself
        if ($payload['email'] === $email) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM approved_users WHERE is_admin = TRUE");
            $stmt->execute();
            if ($stmt->fetchColumn() === 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete own account, at least one admin is required']);
                exit;
            }
        }
        $stmt = $conn->prepare("DELETE FROM approved_users WHERE email = :email AND (is_admin = FALSE OR email = :current_email)");
        $stmt->execute([':email' => $email, ':current_email' => $payload['email']]);
        error_log("User {$payload['user_id']} removed approved user: $email");
        http_response_code(200);
        echo json_encode(['message' => 'User deleted successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log($e->getMessage());
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
}
