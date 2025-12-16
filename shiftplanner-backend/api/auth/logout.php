<?php
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

$config = parse_ini_file('../../private/app.ini');
cors($config);
verify_method(array('GET'));
init_session($config);
session_destroy();
http_response_code(200);
echo json_encode(['message' => 'Logged out successfully']);
exit;
