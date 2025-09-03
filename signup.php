<?php
// Connect to PostgreSQL
include("config.php");
$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $result = pg_query_params($conn,
            "INSERT INTO users (email, password_hash, created_at, updated_at, is_admin) 
            VALUES ($1, $2, NOW(), NOW(), FALSE)",
            [$email, $hashedPassword]
        );

        if ($result) {
            $message = "Signup successful! <a href='login.php'>Login here</a>";
        } else {
            $message = "Signup failed. Please try again.";
        }
    }
}
?>