<?php
session_start();
include("config.php");

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
                echo "✅ Login jsdzjghdjgndjfgk! Welcome, " . htmlspecialchars($row['email']);
                // Example: redirect to dashboard
                // header("Location: dashboard.php");
                // exit;
            } else {
                echo "❌ Incorrect password.";
            }
        } else {
            echo "❌ No account found with that email.";
        }
    } else {
        echo "⚠️ Please enter both email and password.";
    }
}
?>