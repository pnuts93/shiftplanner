<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/util.php';

$method = verify_method(array('GET'));
$config = parse_ini_file("../private/app.ini");
$locale = $config["DEFAULT_LOCALE"];
if (!$locale) {
    $locale = "en";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $locale; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Policy</title>
</head>

<body>
    <h1>Policy</h1>
    <p>This is the policy page.</p>
</body>

</html>