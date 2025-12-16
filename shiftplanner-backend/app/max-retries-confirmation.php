<?php

require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/auth.php';

$content = [
    "en" => [
        "title" => "Too Many Login Attempts",
        "success_block" => "Potential malicious login attempts have been blocked successfully!",
        "success_unblock" => "Account has been unblocked successfully!",
        "error_link" => "Invalid link. Please contact an administrator.",
        "server_error" => "Internal server error. Please try again later."
    ],
    "de" => [
        "title" => "Zu viele Anmeldeversuche",
        "success_block" => "Potenzielle bösartige Anmeldeversuche wurden erfolgreich blockiert!",
        "success_unblock" => "Konto wurde erfolgreich entsperrt!",
        "error_link" => "Ungültiger Link. Bitte kontaktieren Sie einen Administrator.",
        "server_error" => "Interner Serverfehler. Bitte versuchen Sie es später erneut."
    ]
];
$config = parse_ini_file('../private/app.ini');
$conn = db($config);
init_session($config, false);
verify_method(array('GET'));
$locale = $config["DEFAULT_LOCALE"];
if (!$locale) {
    $locale = "en";
}
if (!isset($_GET['uuid'], $_GET['block'])) {
    http_response_code(400);
    echo $content[$locale]["error_link"];
    exit;
}
$uuid = $_GET['uuid'];
$isBlock = $_GET['block'] === 'true';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $content[$locale]["title"] ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        p {
            text-align: center;
            color: rgb(0, 0, 0);
        }
    </style>

<body>
    <p>
        <?php
        try {
            if ($isBlock) {
                $stmt = $conn->prepare("UPDATE suspicious_activities SET confirmed = TRUE WHERE id = :uuid RETURNING remote_ip");
                $stmt->execute([':uuid' => $uuid]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $remote_ip = $result['remote_ip'];
                    add_to_blacklist($remote_ip);
                    http_response_code(200);
                    echo $content[$locale]["success_block"];
                    exit;
                } else {
                    error_log("No matching UUID found for blocking: " . $uuid);
                    error_log("Block attempt from REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . " HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
                    sleep(1); // Prevent brute-force attacks
                    http_response_code(400);
                    echo $content[$locale]["error_link"];
                    exit;
                }
            } else {
                $stmt = $conn->prepare("DELETE FROM suspicious_activities WHERE id = :uuid RETURNING *");
                $stmt->execute([':uuid' => $uuid]);
                $deleted = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($deleted) {
                    error_log("Unblock action for user_id: " . $deleted['user_id']);
                    reset_login_attempts($conn, $deleted['user_id']);
                    http_response_code(200);
                    echo $content[$locale]["success_unblock"];
                    exit;
                } else {
                    error_log("No matching UUID found for blocking: " . $uuid);
                    error_log("Unblock attempt from REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . " HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
                    sleep(1); // Prevent brute-force attacks
                    http_response_code(400);
                    echo $content[$locale]["error_link"];
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            http_response_code(500);
            echo $content[$locale]["server_error"];
            exit;
        }
        ?>
    </p>
</body>

</html>