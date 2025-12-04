<?php
require __DIR__ . '/vendor/autoload.php';
include("config.php");

$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$otpVerified = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If user submits new password form
    if (isset($_POST['new_password']) || isset($_POST['confirm_password'])) {
        $email = trim($_POST['email'] ?? '');
        $newPass = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if ($email === '' || $newPass === '' || $confirm === '') {
            $message = "⚠️ Semua field harus diisi.";
        } elseif ($newPass !== $confirm) {
            $message = "❌ Password dan konfirmasi tidak cocok.";
            $otpVerified = true; // tetap tampilkan form ubah password
        } elseif (strlen($newPass) < 8) {
            $message = "❌ Password minimal 8 karakter.";
            $otpVerified = true;
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $update = pg_query_params($conn, "UPDATE users SET password_hash = $1 WHERE LOWER(email) = LOWER($2)", [$hashed, $email]);

            if ($update === false) {
                $message = "❌ Gagal memperbarui password: " . pg_last_error($conn);
                $otpVerified = true;
            } elseif (pg_affected_rows($update) === 0) {
                $message = "⚠️ Tidak ada akun dengan email tersebut.";
            } else {
                // hapus token/reset entry
                pg_query_params($conn, "DELETE FROM password_resets WHERE LOWER(email) = LOWER($1)", [$email]);
                // sukses -> redirect ke halaman login
                header("Location: login.php?reset=success");
                exit;
            }
        }
    } else {
        // OTP verification branch
        $email = trim($_POST['email'] ?? '');
        $otpInput = trim($_POST['otp'] ?? '');

        if (empty($email) || empty($otpInput)) {
            $message = "⚠️ Please enter your OTP.";
        } else {
            $query = "SELECT token, expires_at FROM password_resets WHERE LOWER(email) = LOWER($1) LIMIT 1";
            $result = pg_query_params($conn, $query, [$email]);

            if ($result && pg_num_rows($result) > 0) {
                $row = pg_fetch_assoc($result);
                $hashedOtp = $row['token'];
                $expiresAt = strtotime($row['expires_at']);

                if (time() > $expiresAt) {
                    $message = "❌ OTP expired. Request a new one.";
                } else if (password_verify($otpInput, $hashedOtp)) {
                    $message = "✅ OTP Verified! Please enter your new password.";
                    $otpVerified = true;
                } else {
                    $message = "❌ Incorrect OTP. Try again.";
                }
            } else {
                $message = "⚠️ No OTP found for this email.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<body>

<h2>Verify OTP</h2>

<!-- Feedback message -->
<?php if (!empty($message)) echo "<p><strong>$message</strong></p>"; ?>

<?php if ($otpVerified): ?>
<form method="POST">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

    <label>New Password:</label> <br>
    <input type="password" id="newpass" name="new_password" required> <br>
    <input type="checkbox" onclick="togglePass1()"> Show Password<br>

    <label>Confirm Password:</label> <br> 
    <input type="password" id="confpass" name="confirm_password" required> <br>

    <input type="checkbox" onclick="togglePass2()"> Show Password <br>

    <button type="submit">Reset Password</button>
</form>

<script>
function togglePass1() {
    var a = document.getElementById("newpass");
    a.type = a.type === "password" ? "text" : "password";
}

function togglePass2() {
    var b = document.getElementById("confpass");
    b.type = b.type === "password" ? "text" : "password";
}
</script>

<?php else: ?>
<form method="POST">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? $_POST['email'] ?? ''); ?>">

    <label>Enter OTP:</label>
    <input type="text" name="otp" required minlength="6" maxlength="6">

    <button type="submit">Verify</button>
</form>
<?php endif; ?>

</body>
</html>