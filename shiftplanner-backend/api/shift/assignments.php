<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
$method = verify_method(array('GET', 'PUT'));
$payload = authenticate($config);

switch ($method) {
    case 'GET':
        get_shifts();
        break;
    case 'PUT':
        upsert_shift();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
}

function get_shifts()
{
    global $conn;
    if (!isset($_GET['month'], $_GET['year'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid month or year']);
        exit;
    }
    $month = intval($_GET['month']);
    $year = intval($_GET['year']);
    // Only dates from the current month to the following 12 months should be provided
    $target_date = \DateTime::createFromFormat('Y-m-d', '' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');

    if (!is_valid_date($target_date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid month or year']);
        exit;
    }
    try {
        $stmt = $conn->prepare("SELECT * FROM assignments WHERE EXTRACT(MONTH FROM date) = :month AND EXTRACT(YEAR FROM date) = :year");
        $stmt->execute([':month' => $month, ':year' => $year]);
        $shifts = [];
        while ($shift = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($shift) {
                $shifts[] = [
                    'userId' => $shift['user_id'],
                    'date' => $shift['date'],
                    'shiftId' => $shift['shift_id']
                ];
            }
        }
        http_response_code(200);
        echo json_encode($shifts);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
}

function upsert_shift()
{
    global $conn;
    global $payload;
    header('Content-Type: application/json');

    // Read and decode JSON body
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['userId'], $data['date'], $data['shiftId'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $userId = intval($data['userId']);
    $date = $data['date'];
    $shiftId = intval($data['shiftId']);

    $target_date = \DateTime::createFromFormat('Y-m-d', $date);

    if (!is_valid_date($target_date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid month or year']);
        exit;
    }
    if ($payload['role'] !== 'admin' && $payload['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    try {
        $upsert_file = fopen("upsert_assignment.sql", "r");
        $stmt = $conn->prepare(stream_get_contents($upsert_file));
        fclose($upsert_file);
        $stmt->execute([':user_id' => $userId, ':shift_date' => $date, ':shift_id' => $shiftId]);
        if ($stmt->rowCount() === 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Shift could not be updated']);
            exit;
        }
        http_response_code(201);
        echo json_encode(['message' => 'Shift added successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
}

function is_valid_date(DateTime $target_date): bool
{
    $min_date = \DateTime::createFromFormat('Y-m-d', date('Y') . '-' . date('m') . '-01');
    $max_date = \DateTime::createFromFormat('Y-m-d', intval($min_date->format('Y')) + 1 . '-' . $min_date->format('m') . '-00');
    return $target_date >= $min_date && $target_date <= $max_date;
}
