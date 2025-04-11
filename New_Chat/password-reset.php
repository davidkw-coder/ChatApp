<?php
require_once 'config.php';

// Redirect if already logged in
redirectIfLoggedIn();

$success = '';
$error = '';

// Handle request password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Email is required';
    } else {
        $conn = getDbConnection();
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Don't reveal if email exists or not for security
            $success = 'If your email is registered, you will receive password reset instructions shortly';
        } else {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $conn->prepare("
                INSERT INTO password_resets (user_id, token, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("iss", $user['id'], $token, $expires);
            
            if ($stmt->execute()) {
                // In a real application, send email with reset link
                // For this example, we'll just show the token
                $resetLink = APP_URL . '/password-reset.php?token=' . $token;
                
                $success = 'Password reset link has been sent to your email';
                
                // For demonstration purposes only
                $success .= '<br><small>(Demo: <a href="' . $resetLink . '">Reset Password</a>)</small>';
            } else {
                $error = 'Failed to generate reset token: ' . $conn->error;
            }
        }
        
        $conn->close();
    }
}

// Handle reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($token) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $conn = getDbConnection();
        
        // Check if token is valid and not expired
        $stmt = $conn->prepare("
            SELECT pr.user_id, u.username 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Invalid or expired token';
        } else {
            $reset = $result->fetch_assoc();
            
            // Hash new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $reset['user_id']);
            
            if ($stmt->execute()) {
                // Mark token as used
                $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                $success = 'Password has been reset successfully. You can now <a href="login.php">login</a> with your new password.';
            } else {
                $error = 'Failed to reset password: ' . $conn->error;
            }
        }
        
        $conn->close();
    }
}

// Check if token is provided in URL
$token = $_GET['token'] ?? '';
$showResetForm = !empty($token);

// Validate token if provided
if ($showResetForm) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT user_id 
        FROM password_resets 
        WHERE token = ? AND expires_at > NOW() AND used = 0
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = 'Invalid or expired token';
        $showResetForm = false;
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="form-container">
            <h2><?php echo $showResetForm ? 'Reset Your Password' : 'Forgot Password'; ?></h2>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($showResetForm): ?>
                <form method="POST" action="password-reset.php">
                    <input type="hidden" name="token" value="<?php echo h($token); ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" action="password-reset.php">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="request_reset" class="btn btn-primary">Request Password Reset</button>
                    </div>
                </form>
                
                <div class="form-links">
                    <p>Remember your password? <a href="login.php">Login</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>

