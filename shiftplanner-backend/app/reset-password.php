<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';

$method = verify_method(array('POST', 'GET'));
$config = parse_ini_file("../private/app.ini");
init_session($config, false);
$conn = db($config);
$locale = $config["DEFAULT_LOCALE"];
if (!$locale) {
    $locale = "en";
}
$content = [
    "en" => [
        "title" => "Reset Password",
        "new_pass" => "New Password",
        "confirm_pass" => "Confirm Password",
        "submit" => "Submit",
        "confirm" => "Password reset successfully. You can now log in with your new password.",
        "error_link" => "Invalid or expired reset link. Please request a new password reset.",
        "error_post" => "Passwords do not match or are invalid. Please try again.",
        "server_error" => "Internal server error. Please try again later."
    ],
    "de" => [
        "title" => "Passwort zurücksetzen",
        "new_pass" => "Neues Passwort",
        "confirm_pass" => "Passwort bestätigen",
        "submit" => "Senden",
        "confirm" => "Passwort erfolgreich zurückgesetzt. Sie können sich jetzt mit Ihrem neuen Passwort anmelden.",
        "error_link" => "Ungültiger oder abgelaufener Link zum Zurücksetzen des Passworts. Bitte fordern Sie einen neuen Link an.",
        "error_post" => "Passwörter stimmen nicht überein oder sind ungültig. Bitte versuchen Sie es erneut.",
        "server_error" => "Interner Serverfehler. Bitte versuchen Sie es später erneut."
    ]
];
$error = null;
try {
    if ($method === 'GET') {
        if (!isset($_GET['otp']) || empty($_GET['otp'])) {
            http_response_code(400);
            $error = $content[$locale]["error_link"];
            throw new Exception("Missing or empty OTP parameter");
        }
        $otp = $_GET['otp'];
        try {
            $stmt = $conn->prepare("SELECT * FROM one_time_tokens WHERE token = :otp AND token_type = 'password_reset' AND expires_at > NOW()");
            $stmt->execute([':otp' => $otp]);
            $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$token_data) {
                error_log('Invalid or expired OTP: ' . $otp);
                http_response_code(403);
                $error = $content[$locale]["error_link"];
                throw new Exception("Invalid or expired OTP");
            }
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            http_response_code(500);
            $error = $content[$locale]["server_error"];
            throw new Exception("Database error");
        }
    } elseif ($method === 'POST') {
        if (!isset($_POST['password'], $_POST['confirm_password'], $_POST['otp']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
            http_response_code(400);
            $error = $content[$locale]["error_post"];
            throw new Exception("Missing password fields");
        }
        if ($_POST['password'] !== $_POST['confirm_password'] || !is_valid_password($_POST['password'])) {
            http_response_code(400);
            $error = $content[$locale]["error_post"];
            throw new Exception("Passwords do not match or are invalid");
        }
        try {
            // Delete token
            $stmt = $conn->prepare("DELETE FROM one_time_tokens WHERE token = :otp AND token_type = 'password_reset' AND expires_at > NOW() RETURNING *");
            $stmt->execute([':otp' => $_POST['otp']]);
            $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$token_data) {
                http_response_code(403);
                $error = $content[$locale]["error_link"];
                throw new Exception("Invalid or expired OTP");
            }
            // Update password
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
            $stmt->execute([':password' => $hashedPassword, ':user_id' => $token_data['user_id']]);
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            http_response_code(500);
            $error = $content[$locale]["server_error"];
            throw new Exception("Database error");
        }
    }
} catch (Exception $e) {
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

        .container {
            max-width: 600px;
            margin: auto;
            background-color: #1E1A1D;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: white;
        }

        p,
        label {
            color: #cccccc;
        }

        a {
            color: #FFABF3;
            text-decoration: none;
        }

        button {
            background-color: black;
            color: #FFABF3;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1><?php echo $content[$locale]["title"] ?></h1>
        <?php if ($error): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php elseif ($method === "GET"): ?>
            <form action="/app/reset-password.php" method="POST">
                <label for="password"><?php echo $content[$locale]["new_pass"] ?></label>
                <input type="password" id="password" name="password" minlength="8" maxlength="255" placeholder="********" required>
                <label for="confirm_password"><?php echo $content[$locale]["confirm_pass"] ?></label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="255" placeholder="********" required>
                <input type="hidden" name="otp" value="<?php echo htmlspecialchars($otp); ?>">
                <button type="submit"><?php echo $content[$locale]["submit"] ?></button>
            </form>
        <?php else: ?>
            <p><?php echo $content[$locale]["confirm"] ?></p>
        <?php endif; ?>
    </div>
</body>