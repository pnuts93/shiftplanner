<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';


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
    if ($config["ENV"] === "dev") {
        error_log("Email content for email confirmation: " . $message);
    } else {
        send_email($email, $subject, $message, $config);
    }
}

function prepare_forgot_password($otp, $email, $config)
{
    $subject = "Reset Password";
    $locale = $config["DEFAULT_LOCALE"] ?? "en";
    $message = file_get_contents(__DIR__ . "/templates/" . $locale . "/forgot_password.html");
    $link = "https://" .  $config["DOMAIN"] . "/app/reset-password.php?otp=" . urlencode($otp);
    $message = str_replace(
        ['[confirmation_link]', '[email]'],
        [$link, htmlspecialchars($email)],
        $message
    );
    if ($config["ENV"] === "dev") {
        error_log("Email content for forgot password: " . $message);
    } else {
        send_email($email, $subject, $message, $config);
    }
}

function prepare_max_retries($to, $config, $uuid)
{
    $subject = "Too Many Login Attempts";
    $locale = $config["DEFAULT_LOCALE"] ?? "en";
    $message = file_get_contents(__DIR__ . "/templates/" . $locale . "/max_retries.html");
    if ($message === false) {
        $message = file_get_contents(__DIR__ . "/templates/en/max_retries.html");
    }
    $message = str_replace(
        ['[max_retries_link]'],
        ["https://" . $config["DOMAIN"] . "/app/max-retries-confirmation.php?uuid=" . urlencode($uuid)],
        $message
    );
    if ($config["ENV"] === "dev") {
        error_log("Email content for max retries: " . $message);
    } else {
        send_email($to, $subject, $message, $config);
    }
}

function prepare_shift_change_notification($to, $locale, $shift_date, $shift_type, $config)
{
    $subject = "Shift Assignment Notification";
    $locale = $config["DEFAULT_LOCALE"] ?? "en";
    $message = file_get_contents(__DIR__ . "/templates/" . $locale . "/shift_change_notification.html");
    if ($message === false) {
        $message = file_get_contents(__DIR__ . "/templates/en/shift_change_notification.html");
    }
    $formatted_date = date('d.m.Y', strtotime($shift_date));
    $message = str_replace(
        ['[shift_date]', '[shift_type]'],
        [htmlspecialchars($formatted_date), htmlspecialchars($shift_type)],
        $message
    );
    if ($config["ENV"] === "dev") {
        error_log("Email content for shift change: " . $message);
    } else {
        send_email($to, $subject, $message, $config);
    }
}

function send_email($to, $subject, $message, $config)
{

    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host =  $config["SMTP_HOST"];
        $mail->SMTPAuth = true;
        $mail->Username =  $config["SMTP_EMAIL"];
        $mail->Password =  $config["SMTP_PASSWORD"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port =  $config["SMTP_PORT"];

        //Recipients
        $mail->setFrom($config["NO_REPLY_EMAIL"], 'No Reply');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        error_log("Email sent successfully");
    } catch (Exception $e) {
        error_log("Email could not be sent: {$mail->ErrorInfo}");
    }
}
