<?php
session_start();
include("config.php");

$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$email = $_GET['email'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otpInput = trim($_POST['otp']);

    // Ambil OTP record
    $result = pg_query_params($conn,
        "SELECT otp, expires_at FROM email_verification WHERE email=$1",
        [$email]
    );

    if (pg_num_rows($result) === 0) {
        $message = "No OTP found. Please request a new one.";
    } else {
        $row = pg_fetch_assoc($result);

        // Cek expired
        if (strtotime($row['expires_at']) < time()) {
            $message = "OTP expired. Request new OTP.";
        }
        // Cek OTP benar
        elseif (!password_verify($otpInput, $row['otp'])) {
            $message = "Incorrect OTP. Try again.";
        }
        else {
            // OTP benar → buat akun
            if (!isset($_SESSION['pending_password'])) {
                $message = "Session expired. Please register again.";
            } else {
                $hashedPassword = $_SESSION['pending_password'];

                pg_query_params($conn,
                    "INSERT INTO users (email, password_hash, created_at)
                     VALUES ($1, $2, NOW())",
                    [$email, $hashedPassword]
                );

                // hapus otp biar aman
                pg_query_params($conn, "DELETE FROM email_verification WHERE email=$1", [$email]);

                unset($_SESSION['pending_password']);

                header("Location: dashboard.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Email</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; }
        .box {
            width: 350px; margin: 80px auto; padding: 20px;
            background: white; border-radius: 8px; box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        input { width: 100%; padding: 10px; margin-top: 10px; }
        button { width: 100%; padding: 10px; margin-top: 12px; background: #2b7cff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .msg { color: red; margin-top: 10px; }
    </style>
</head>

<body>
    <div class="box">
        <h2>Verify Your Email</h2>
        <p>Enter the OTP sent to: <b><?php echo htmlspecialchars($email); ?></b></p>

        <?php if ($message): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="otp" placeholder="Enter OTP" required>
            <button type="submit">Verify</button>
        </form>
    </div>
</body>
</html>