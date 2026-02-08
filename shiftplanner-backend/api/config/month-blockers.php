<?php
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
$method = verify_method(array('GET', 'POST', 'DELETE'));
init_session($config);


switch ($method) {
    case 'GET':
        get_month_blockers();
        break;
    case 'POST':
        create_month_blocker();
        break;
    case 'DELETE':
        delete_month_blocker();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
}

function get_month_blockers()
{
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM month_blockers WHERE (year = EXTRACT(YEAR FROM CURRENT_DATE) AND month >= EXTRACT(month FROM CURRENT_DATE)) OR year > EXTRACT(YEAR FROM CURRENT_DATE) ORDER BY year, month");
    $stmt->execute();
    $month_blockers = [];
    while ($month_blocker = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($month_blocker) {
            $month_blockers[] = [
                'year' => $month_blocker['year'],
                'month' => $month_blocker['month']
            ];
        }
    }
    http_response_code(200);
    echo json_encode($month_blockers);
}

function create_month_blocker()
{
    global $conn;

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['year'], $data['month'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    $year = intval($data['year']);
    $month = intval($data['month']);

    if ($_SESSION['token'] !== $_SERVER['HTTP_X_CSRF_TOKEN'] || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    try {
        $stmt = $conn->prepare("INSERT INTO month_blockers (year, month) VALUES (:year, :month)");
        $stmt->execute([':year' => $year, ':month' => $month]);
        http_response_code(201);
        echo json_encode(['message' => 'Month blocker created successfully']);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function delete_month_blocker()
{
    global $conn;

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['year'], $data['month'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    $year = intval($data['year']);
    $month = intval($data['month']);

    if ($_SESSION['token'] !== $_SERVER['HTTP_X_CSRF_TOKEN'] || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    try {
        $stmt = $conn->prepare("DELETE FROM month_blockers WHERE year = :year AND month = :month");
        $stmt->execute([':year' => $year, ':month' => $month]);
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Month blocker not found']);
            exit;
        }
        http_response_code(200);
        echo json_encode(['message' => 'Month blocker deleted successfully']);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
