<?php
session_start();
include("config.php");

$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
$message = "";

if (!$conn) {
    die("❌ Connection failed.");
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        // Check if user exists
        $result = pg_query_params($conn,
            "SELECT id, email, password_hash FROM users WHERE email = $1",
            [$email]
        );

        if ($row = pg_fetch_assoc($result)) {
            // Verify password
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $message = "✅ Login successful! Welcome, " . htmlspecialchars($row['email']);
                // Example: redirect to dashboard
                header("Location: dashboard.php");
                // exit;
            } else {
                $message = "❌ Incorrect password.";
            }
        } else {
            $message = "❌ No account found with that email.";
        }
    } else {
        $message = "⚠️ Please enter both email and password.";
    }
}
?>
<!DOCTYPE html>
<html>
<body>



<form method="POST">
    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" id="password" name="password" required> <br>
    <input type="checkbox" id="showPassword" > Show Password<br>
    <?php if (!empty($message)) : ?>
    <label><strong><?php echo $message; ?></strong></label> <br>
    <?php endif; ?>
    <button type="submit">Login</button>
    <br><br>
    <a href="forgotpassword.html">Forgot Password?</a><br>
    <a href="signup.html">Don't have an account? Sign Up</a>
</form>

</body>
</html>
<script>
    const passwordField = document.getElementById('password');
    const toggle = document.getElementById('showPassword');

    toggle.addEventListener('change', function () {
        passwordField.type = this.checked ? 'text' : 'password';
    });
</script>
<?php
