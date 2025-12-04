<?php
require __DIR__ . '/vendor/autoload.php';
include("config.php");

$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otpInput = trim($_POST['otp'] ?? '');

    if (empty($email) || empty($otpInput)) {
        die("⚠️ Missing email or OTP.");
    }

    // Fetch OTP hash from database
    $query = "SELECT token, expires_at FROM password_resets WHERE email = $1 LIMIT 1";
    $result = pg_query_params($conn, $query, [$email]);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $hashedOtp = $row['token'];
        $expiresAt = strtotime($row['expires_at']);

        // Check if expired
        if (time() > $expiresAt) {
            echo "❌ OTP expired. Please request a new one.";
            exit();
        }

        // Verify OTP
        if (password_verify($otpInput, $hashedOtp)) {

            // OPTIONAL: Clear used token
            pg_query_params($conn, 
                "DELETE FROM password_resets WHERE email = $1",
                [$email]
            );

            echo "<h3>✅ OTP Verified!</h3>";
            echo "<p>You can now reset your password.</p>";

            // Redirect to reset password page if you want
            // header("Location: resetpassword.php?email=" . urlencode($email));
            exit();

        } else {
            echo "❌ Incorrect OTP. Please try again.";
            exit();
        }
    } else {
        echo "⚠️ No OTP found for this email. Request again.";
        exit();
    }
}

pg_close($conn);
?>