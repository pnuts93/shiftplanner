<?php
$content = [
    "en" => [
        "title" => "Email Confirmation",
        "success" => "<p>Email confirmed successfully!</p>",
        "error_link" => "<p>Invalid or expired reset link. Please request a new confirmation email.</p>",
        "server_error" => "<p>Internal server error. Please try again later.</p>"
    ],
    "de" => [
        "title" => "Email-Bestätigung",
        "success" => "<p>Email wurde erfolgreich bestätigt!</p>",
        "error_link" => "<p>Ungültiger oder abgelaufener Link zum Zurücksetzen des Passworts. Bitte fordern Sie eine neue Bestätigungsemail.</p>",
        "server_error" => "<p>Interner Serverfehler. Bitte versuchen Sie es später erneut.</p>"
    ]
];
$config = parse_ini_file('../private/app.ini');
$locale = $config["DEFAULT_LOCALE"];
if (!$locale) {
    $locale = "en";
}
?>
<!DOCTYPE html>
<html>

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
</head>

<body>
    <?php
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../lib/util.php';
    require_once __DIR__ . '/../lib/auth.php';

    $conn = db($config);
    cors($config);
    verify_method(array('GET'));
    if (!isset($_GET['otp'])) {
        http_response_code(400);
        echo $content[$locale]["error_link"];
        exit;
    }

    $otp = $_GET['otp'];
    if ($otp === '') {
        http_response_code(400);
        echo $content[$locale]["error_link"];
        exit;
    }
    try {
        $stmt = $conn->prepare("DELETE FROM one_time_tokens WHERE token = :otp AND token_type = 'email_confirmation' RETURNING *");
        $stmt->execute([':otp' => $otp]);
        $email_confirmation_token = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$email_confirmation_token || strtotime($email_confirmation_token['expires_at']) < time()) {
            http_response_code(403);
            echo $content[$locale]["error_link"];
            exit;
        }

        // Update user status to confirmed
        $stmt = $conn->prepare("UPDATE users SET is_email_confirmed = TRUE WHERE id = :user_id");
        $stmt->execute([':user_id' => $email_confirmation_token['user_id']]);

        http_response_code(200);
        echo $content[$locale]["success"];
        // Cleanup
        $stmt = $conn->prepare("DELETE FROM one_time_tokens WHERE expires_at < NOW()");
        $stmt->execute();
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        http_response_code(500);
        echo $content[$locale]["server_error"];
    }
    ?>
</body>

</html>