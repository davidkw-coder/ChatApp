<?php
require_once 'config.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $conn = getDbConnection();
        
        if ($conn === false) {
            $error = 'Database connection failed';
        } else {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            
            if ($stmt === false) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = 'Username already exists';
                } else {
                    // Check if email already exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    
                    if ($stmt === false) {
                        $error = 'Database error: ' . $conn->error;
                    } else {
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $error = 'Email already exists';
                        } else {
                            // Hash password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert new user
                            $stmt = $conn->prepare("INSERT INTO users (username, email, password, status, created_at) VALUES (?, ?, ?, 'online', NOW())");
                            
                            if ($stmt === false) {
                                $error = 'Database error: ' . $conn->error;
                            } else {
                                $stmt->bind_param("sss", $username, $email, $hashed_password);
                                
                                if ($stmt->execute()) {
                                    $user_id = $conn->insert_id;
                                    
                                    // Set session
                                    $_SESSION['user_id'] = $user_id;
                                    $_SESSION['username'] = $username;
                                    
                                    // Redirect to chat
                                    header('Location: chat.php');
                                    exit;
                                } else {
                                    $error = 'Registration failed: ' . $conn->error;
                                }
                            }
                        }
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
    <title>Register - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Create a new account</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>
            
            <div class="form-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>

