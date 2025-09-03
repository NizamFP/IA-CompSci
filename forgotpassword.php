<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include database config and connect
include("config.php");
$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!empty($email)) {
        $query = "SELECT * FROM users WHERE email = $1 LIMIT 1";
        $result = pg_query_params($conn, $query, [$email]);

        if ($result && pg_num_rows($result) > 0) {
            $reset_link = "http://youtube.com";
            $otp = random_int(100000, 999999);
            $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
            $expiry = date('Y-m-d H:i:s', time() + 300); // Expire in 5 minutes

            pg_query_params($conn, 
                "INSERT INTO password_resets (email, token, expires_at)
                 VALUES ($1, $2, $3)
                 ON CONFLICT (email) DO UPDATE
                 SET token = $2, expires_at = $3",
                [$email, $hashedOtp, $expiry]
            );
            
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nizam.fprayogo@gmail.com'; 
                $mail->Password = 'fqbv polm skmo ywzv'; // Replace with App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('nizam.fprayogo@gmail.com', 'CBT System by Nizam');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "$otp is your verification code";
                    $mail->Body = "
                    <p>Your One-Time Passcode (OTP) is:</p>
                    <h2 style='color:#007BFF;'>$otp</h2>
                    <p>This code will expire in <strong>5 minutes</strong>. If you didn't request this, ignore the email.</p>
                ";
                $mail->AltBody = "Your OTP is: $otp. It will expire in 5 minutes.";
                $mail->send();
                header("Location: forgotpassword.html?msg=OTP sent!");
                exit();
            } catch (Exception $e) {
                echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            header("Location: verifyotp.html?msg=sent");
            exit();
        }
    } else {
        echo "⚠️ Please enter a valid email.";
    }
}

pg_close($conn);
?>