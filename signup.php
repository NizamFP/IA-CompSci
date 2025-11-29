<?php
// Connect to PostgreSQL
include("config.php");
$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // 1. Cek apakah email sudah dipakai
    $check = pg_query_params($conn, "SELECT * FROM users WHERE email=$1", [$email]);
    if (pg_num_rows($check) > 0) {
        $message = "Email already used.";
    } else {
        // 2. Generate OTP
        $otp = random_int(100000, 999999);
        $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', time() + 300);

        // 3. Simpan ke table verification
        pg_query_params($conn,
            "INSERT INTO email_verification (email, otp, expires_at)
             VALUES ($1, $2, $3)
             ON CONFLICT (email) DO UPDATE SET otp=$2, expires_at=$3",
            [$email, $hashedOtp, $expires]
        );

        // 4. Kirim OTP via email
        sendVerificationEmail($email, $otp);

        // 5. Store TEMP password di session
        $_SESSION['pending_password'] = password_hash($password, PASSWORD_DEFAULT);

        // 6. Redirect user ke page Verify
        header("Location: verifyemail.php?email=" . urlencode($email));
        exit;
    }
}
?>