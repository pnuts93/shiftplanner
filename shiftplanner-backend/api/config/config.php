<?php
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

$config = parse_ini_file('../../private/app.ini');
$conn = db($config);
cors($config);
$method = verify_method(array('GET'));
init_session($config);
$stmt = $conn->prepare("SELECT * FROM shifts");
$stmt->execute();
$shifts = [];
$result = [];
while ($shift = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($shift) {
        $shifts[] = [
            'id' => $shift['id'],
            'shiftStartH' => $shift['shift_start_h'],
            'shiftEndH' => $shift['shift_end_h'],
            'name' => $shift['name'],
            'displayName' => $shift['display_name'],
            'maxWorkers' => $shift['max_workers'],
            'minExperiencedWorkers' => $shift['min_experienced_workers'],
            'maxExperiencedWorkers' => $shift['max_experienced_workers'],
            'isWorkingShift' => $shift['is_working_shift'] == 1
        ];
    }
}
$result['shifts'] = $shifts;
$result['experiencedYearsThreshold'] = intval($config['EXP_YEARS_THRESHOLD']);
$result['maxMonthOffset'] = intval($config['MAX_MONTH_OFFSET']);
http_response_code(200);
echo json_encode($result);
