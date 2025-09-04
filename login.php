<?php
session_start();
include("config.php");
$message = $_SESSION['message'] ?? "";
unset($_SESSION['message']); // clear after showing once

$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);

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
                header("Location: dashboard.php");
            } else {
                $_SESSION['message'] = "❌ Incorrect password.";
                header("Location: login.php");
                exit;
            }
        } else {
            $_SESSION['message'] = "❌ No account found with that email.";
            header("Location: login.php");
            exit;
        }
    } else {
        $message = "⚠️ Please enter both email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <script>
    function togglePassword() {
      const pwField = document.getElementById("password");
      pwField.type = pwField.type === "password" ? "text" : "password";
    }
  </script>
</head>
<body>
  <div class="box">
    <h2>Login</h2>
    
    <form method="POST" action="">
        <label>Email</label><br>
        <input type="email" name="email" required><br>

        <label>Password</label><br>
        <input type="password" id="password" name="password" required><br>

        <input type="checkbox" onclick="togglePassword()"> Show Password<br>

        <label id="announcement" style="color:red;">
            <?php echo htmlspecialchars($message); ?> <br>
        </label>

        <button type="submit">Enter</button>
        <br><a href="forgotpassword.html">Forgot Password</a> <br>
        <a href="signup.html">New? Sign Up</a>
    </form>
  </div>
</body>
</html>