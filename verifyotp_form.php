<?php
$email = $_GET['email'] ?? '';
?>
<form action="verifyotp_process.php" method="POST">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
    <label>Enter OTP:</label>
    <input type="text" name="otp" required minlength="6" maxlength="6">
    <button type="submit">Verify</button>
</form>