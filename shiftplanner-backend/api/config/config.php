<?php
require_once __DIR__ . '/../../lib/util.php';
require_once __DIR__ . '/../../lib/auth.php';

$config = parse_ini_file('../../private/app.ini');
cors($config);
$method = verify_method(array('GET'));
init_session($config);

$raw_content = file_get_contents(__DIR__ . '/../../config/config.json');
$json_data = json_decode($raw_content, true);
http_response_code(200);
echo json_encode($json_data);
