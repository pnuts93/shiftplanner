<?php
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../email/send-email.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
$method = verify_method(array('PUT'));
init_session($config);

header('Content-Type: application/json');

// Read and decode JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['userId'], $data['date'], $data['shiftId'], $data['isMarkedImportant'], $data['userComment'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$userId = intval($data['userId']);
$date = $data['date'];
$shiftId = intval($data['shiftId']);
$isMarkedImportant = strlen($data['isMarkedImportant']) === 0 ? 0 : 1;
$userComment = $data['userComment'];

$target_date = \DateTime::createFromFormat('Y-m-d', $date);

check_is_valid_date($target_date, $conn, $config);
if ($_SESSION['token'] !== $_SERVER['HTTP_X_CSRF_TOKEN'] || $_SESSION['user_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}
try {
    $stmt = $conn->prepare("UPDATE assignments SET is_marked_important = :is_marked_important, user_comment = :user_comment WHERE user_id = :user_id AND date = :shift_date");
    $stmt->execute([':user_id' => $userId, ':shift_date' => $date, ':is_marked_important' => $isMarkedImportant, ':user_comment' => $userComment]);
    if ($stmt->rowCount() !== 1) {
        http_response_code(500);
        echo json_encode(['error' => 'Shift could not be updated']);
        exit;
    }
    http_response_code(201);
    echo json_encode(['message' => 'Shift updated successfully']);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
