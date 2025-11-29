<?php
require __DIR__ . '/vendor/autoload.php';
include("config.php");

$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$otpVerified = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otpInput = trim($_POST['otp'] ?? '');

    if (empty($email) || empty($otpInput)) {
        $message = "⚠️ Please enter your OTP.";
    } else {
        $query = "SELECT token, expires_at FROM password_resets WHERE email = $1 LIMIT 1";
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