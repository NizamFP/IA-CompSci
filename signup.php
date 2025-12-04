<?php
session_start();
include("mailer.php");
include("config.php");

$message = '';
$email = '';

$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
if (!$conn) {
    $message = "Connection failed: " . pg_last_error();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Alamat email tidak valid.";
    } elseif ($password === '') {
        $message = "Masukkan password.";
    } elseif (!$conn) {
        $message = "Database tidak tersedia. Coba lagi nanti.";
    } else {
        // case-insensitive check
        $check = pg_query_params($conn, "SELECT 1 FROM users WHERE LOWER(email) = LOWER($1) LIMIT 1", [$email]);

        if ($check === false) {
            $message = "Kesalahan database: " . pg_last_error($conn);
        } elseif (pg_num_rows($check) > 0) {
            $message = "Email sudah digunakan.";
        } else {
            $otp = random_int(100000, 999999);
            $hashedOtp = password_hash((string)$otp, PASSWORD_DEFAULT);
            $expires = date('Y-m-d H:i:s', time() + 300);

            $res = pg_query_params($conn,
                "INSERT INTO email_verification (email, otp, expires_at)
                 VALUES ($1, $2, $3)
                 ON CONFLICT (email) DO UPDATE SET otp = EXCLUDED.otp, expires_at = EXCLUDED.expires_at",
                [$email, $hashedOtp, $expires]
            );

            if ($res === false) {
                $message = "Gagal menyimpan kode verifikasi: " . pg_last_error($conn);
            } else {
                // kirim email verifikasi (fungsi di mailer.php)
                sendVerificationEmail($email, $otp);

                // simpan password sementara (hash) untuk proses verifikasi
                $_SESSION['pending_password'] = password_hash($password, PASSWORD_DEFAULT);

                header("Location: verifyemail.php?email=" . urlencode($email));
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sign up</title>
</head>
<body>
    <div class="box">
        <h2>Sign Up</h2>

        <?php if ($message !== ''): ?>
            <p class="feedback"><strong><?php echo htmlspecialchars($message); ?></strong></p>
        <?php endif; ?>

        <form action="" method="POST">
            <label>Email</label> <br>
            <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>"><br>

            <label>Password</label> <br>
            <input type="password" name="password" placeholder="Password" id="password" required><br>

            <input type="checkbox" id="showPass"> Show Password<br><br>

            <button type="submit">Enter</button>
        </form>

        <a href="login.php">Already have an account? Sign in</a>
    </div>

    <script>
        document.getElementById("showPass").addEventListener("click", function() {
            const pw = document.getElementById("password");
            pw.type = this.checked ? "text" : "password";
        });
    </script>
</body>
</html>