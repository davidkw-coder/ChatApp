<?php
require_once 'config.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        $conn = getDbConnection();
        
        if ($conn === false) {
            $error = 'Database connection failed';
        } else {
            // Get user from database
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            
            if ($stmt === false) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $error = 'Invalid username or password';
                } else {
                    $user = $result->fetch_assoc();
                    
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        
                        // Update user status
                        $stmt = $conn->prepare("UPDATE users SET status = 'online', last_active = NOW() WHERE id = ?");
                        
                        if ($stmt === false) {
                            error_log("Prepare failed: " . $conn->error);
                        } else {
                            $stmt->bind_param("i", $user['id']);
                            $stmt->execute();
                        }
                        
                        // Redirect to chat
                        header('Location: chat.php');
                        exit;
                    } else {
                        $error = 'Invalid username or password';
                    }
                }
            }
            
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Login to your account</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Login</button>
                    <a href="password-reset.php">Forgot password?</a>
                </div>
            </form>
            
            <div class="form-links">
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>

