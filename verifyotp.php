<?php
require __DIR__ . '/vendor/autoload.php';
include("config.php");

// Connect to DB
$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $enteredOtp = trim($_POST['otp'] ?? '');

    if (!empty($email) && !empty($enteredOtp)) {
        // Fetch OTP and expiry for the email
        $result = pg_query_params($conn,
            "SELECT token, expires_at FROM password_resets WHERE email = $1",
            [$email]
        );
        $row = pg_fetch_assoc($result);

        if ($row) {
            $hashedOtp = $row['token'];
            $expiry = strtotime($row['expires_at']);

            if (time() > $expiry) {
                $message = "❌ OTP has expired. Please request a new one.";
            } elseif (password_verify($enteredOtp, $hashedOtp)) {
                pg_query_params($conn, "DELETE FROM password_resets WHERE email = $1", [$email]);
                header("Location: verified.html");
                exit();
            } else {
                $message = "❌ Invalid OTP. Please try again.";
            }
        } else {
            $message = "❌ No OTP request found for this email.";
        }
    } else {
        $message = "⚠️ Please provide both email and OTP.";
    }
}

pg_close($conn);

include("verifyotp.html");
exit();
?>