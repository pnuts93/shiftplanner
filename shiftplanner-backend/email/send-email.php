<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function prepare_email_confirmation($otp, $email, $first_name, $config)
{
    $subject = "Email Confirmation";
    $locale = $config["DEFAULT_LOCALE"] ?? "en";
    $message = file_get_contents(__DIR__ . "/templates/" . $locale . "/confirm_email.html");
    if ($message === false) {
        $message = file_get_contents(__DIR__ . "/templates/en/confirm_email.html");
    }
    $link = "https://" . $config["SMTP_HOST"] . "/app/email-confirmation.php?otp=" . urlencode($otp);
    $message = str_replace(
        ['[confirmation_link]', '[username]'],
        [$link, htmlspecialchars($first_name)],
        $message
    );
    send_email($email, $subject, $message, $config);
}

function prepare_forgot_password($otp, $email, $config)
{
    $subject = "Reset Password";
    $locale = $config["DEFAULT_LOCALE"] ?? "en";
    $message = file_get_contents(__DIR__ . "/templates/" . $locale . "/forgot_password.html");
    $link = "https://" .  $config["SMTP_HOST"] . "/app/reset-password.php?otp=" . urlencode($otp);
    $message = str_replace(
        ['[confirmation_link]', '[email]'],
        [$link, htmlspecialchars($email)],
        $message
    );
    send_email($email, $subject, $message, $config);
}

function send_email($to, $subject, $message, $config)
{
    error_log("Sending email to: $to\nSubject: $subject\nMessage: $message");
    /*
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host =  $config["SMTP_HOST"];
        $mail->SMTPAuth = true;
        $mail->Username =  $config["SMTP_EMAIL"];
        $mail->Password =  $config["SMTP_PASSWORD"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port =  $config["SMTP_PORT"];

        //Recipients
        $mail->setFrom( $config["NO_REPLY_EMAIL"], 'No Reply');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
        */
}
