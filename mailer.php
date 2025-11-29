<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . "/vendor/autoload.php";

function baseMailer() {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nizam.fprayogo@gmail.com';
        $mail->Password = 'fqbv polm skmo ywzv';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('nizam.fprayogo@gmail.com', 'CBT System');
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer init error: {$mail->ErrorInfo}");
    }
    return null;
}

function sendVerificationEmail($email, $otp) {
    $mail = baseMailer();
    if (!$mail) return;

    try {
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Your Account Verification Code: $otp";
        $mail->Body = "
            <p>Your verification code is:</p>
            <h2 style='color:#007BFF;'>$otp</h2>
            <p>This code expires in <strong>5 minutes</strong>.</p>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
    }
}

function sendPasswordResetEmail($email, $otp) {
    $mail = baseMailer();
    if (!$mail) return;

    try {
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Reset Your Password - OTP: $otp";
        $mail->Body = "
            <p>Your password reset OTP is:</p>
            <h2>$otp</h2>
            <p>Expires in 5 minutes.</p>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
    }
}
?>