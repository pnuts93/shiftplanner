<?php
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../email/send-email.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
$method = verify_method(array('GET', 'PUT', 'DELETE'));
init_session($config);

switch ($method) {
    case 'GET':
        get_shifts();
        break;
    case 'PUT':
        upsert_shift();
        break;
    case 'DELETE':
        delete_shift();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
}

function get_shifts()
{
    global $conn;
    global $config;

    if (!isset($_GET['month'], $_GET['year'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid month or year']);
        exit;
    }
    $month = intval($_GET['month']);
    $year = intval($_GET['year']);
    // Only dates from the current month to the following 12 months should be provided
    $target_date = \DateTime::createFromFormat('Y-m-d', '' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');

    check_is_valid_date($target_date, $conn, $config, false);

    try {
        $stmt = $conn->prepare("SELECT * FROM assignments WHERE EXTRACT(MONTH FROM date) = :month AND EXTRACT(YEAR FROM date) = :year");
        $stmt->execute([':month' => $month, ':year' => $year]);
        $shifts = [];
        while ($shift = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($shift) {
                $shifts[] = [
                    'userId' => $shift['user_id'],
                    'date' => $shift['date'],
                    'shiftId' => $shift['shift_id'],
                    'isMarkedImportant' => boolval($shift['is_marked_important']),
                    'userComment' => $shift['user_comment'],
                ];
            }
        }
        http_response_code(200);
        echo json_encode($shifts);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function upsert_shift()
{
    global $conn;
    global $config;

    header('Content-Type: application/json');

    // Read and decode JSON body
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['userId'], $data['date'], $data['shiftId'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $userId = intval($data['userId']);
    $date = $data['date'];
    $shiftId = intval($data['shiftId']);

    $target_date = \DateTime::createFromFormat('Y-m-d', $date);

    check_is_valid_date($target_date, $conn, $config);
    if ($_SESSION['token'] !== $_SERVER['HTTP_X_CSRF_TOKEN'] || ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] !== $userId)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    try {
        $upsert_file = file_get_contents("upsert-assignment.sql");
        $stmt = $conn->prepare($upsert_file);
        $stmt->execute([':user_id' => $userId, ':shift_date' => $date, ':shift_id' => $shiftId, ':experienced_years_threshold' => $config['EXP_YEARS_THRESHOLD'] . ' years']);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$shift) {
            http_response_code(409);
            echo json_encode(['error' => 'Shift could not be updated']);
            exit;
        }
        http_response_code(201);
        echo json_encode(['message' => 'Shift added successfully']);
        // send notification if the user changed someone else's shift and has notifications enabled
        if ($_SESSION['user_id'] !== $userId) {
            error_log("User {$_SESSION['user_id']} created/updated assignment {$shift['display_name']} for user $userId on date $date");
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit;
            }
            if (boolval($user['is_notified_shift_change'])) {
                prepare_shift_change_notification(
                    $user['email'],
                    $user['locale'],
                    $date,
                    $shift["display_name"],
                    $config
                );
            }
        }
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function delete_shift()
{
    global $conn;
    global $config;

    header('Content-Type: application/json');

    // Read and decode JSON body
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['userId'], $data['date'], $data['shiftId'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $userId = intval($data['userId']);
    $date = $data['date'];

    $target_date = \DateTime::createFromFormat('Y-m-d', $date);

    check_is_valid_date($target_date, $conn, $config);
    if ($_SESSION['token'] !== $_SERVER['HTTP_X_CSRF_TOKEN'] || ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] !== $userId)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    try {
        $stmt = $conn->prepare("DELETE FROM assignments a WHERE user_id = :user_id AND date = :shift_date RETURNING (SELECT display_name FROM shifts WHERE id = a.shift_id) AS display_name");
        $stmt->execute([':user_id' => $userId, ':shift_date' => $date]);
        $deleted_shift = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$deleted_shift) {
            http_response_code(409);
            echo json_encode(['error' => 'Shift could not be deleted']);
            exit;
        }
        http_response_code(201);
        echo json_encode(['message' => 'Shift deleted successfully']);
        // send notification if the user changed someone else's shift and has notifications enabled
        if ($_SESSION['user_id'] !== $userId) {
            error_log("User {$_SESSION['user_id']} deleted assignment {$deleted_shift['display_name']} for user $userId on date $date");
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit;
            }
            #TODO: check if email should be sent for assignment deletion
            if (boolval($user['is_notified_shift_change'])) {
                prepare_shift_change_notification(
                    $user['email'],
                    $user['locale'],
                    $date,
                    $deleted_shift["display_name"],
                    $config
                );
            }
        }
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
