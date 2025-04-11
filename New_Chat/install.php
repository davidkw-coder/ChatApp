<?php
require_once 'config.php';

$success = '';
$error = '';

// Check if already installed
$installed = false;
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        $error = 'Database connection failed: ' . $conn->connect_error;
    } else {
        // Check if database exists
        $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
        $dbExists = $result->num_rows > 0;
        
        if ($dbExists) {
            // Select the database
            $conn->select_db(DB_NAME);
            
            // Check if users table exists
            $result = $conn->query("SHOW TABLES LIKE 'users'");
            $installed = $result->num_rows > 0;
            
            if ($installed) {
                $success = 'ChatApp is already installed. <a href="index.php">Go to homepage</a>';
            }
        }
    }
} catch (Exception $e) {
    $error = 'Error checking installation: ' . $e->getMessage();
}

// Handle installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $adminUsername = $_POST['admin_username'] ?? '';
    $adminEmail = $_POST['admin_email'] ?? '';
    $adminPassword = $_POST['admin_password'] ?? '';
    
    if (empty($adminUsername) || empty($adminEmail) || empty($adminPassword)) {
        $error = 'All fields are required';
    } else {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
            
            if ($conn->connect_error) {
                $error = 'Database connection failed: ' . $conn->connect_error;
            } else {
                // Create database if it doesn't exist
                $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
                $conn->select_db(DB_NAME);
                
                // Read SQL file
                $sql = file_get_contents('db.sql');
                
                // Execute SQL queries
                if ($conn->multi_query($sql)) {
                    // Wait for all queries to finish
                    while ($conn->more_results() && $conn->next_result()) {
                        // Consume all results
                    }
                    
                    // Create admin user if not already created in SQL
                    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        INSERT INTO users (username, email, password, status, is_admin, created_at) 
                        VALUES (?, ?, ?, 'online', 1, NOW())
                        ON DUPLICATE KEY UPDATE email = VALUES(email), password = VALUES(password)
                    ");
                    
                    if ($stmt === false) {
                        throw new Exception('Error creating admin user: ' . $conn->error);
                    }
                    
                    $stmt->bind_param("sss", $adminUsername, $adminEmail, $hashedPassword);
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Error creating admin user: ' . $stmt->error);
                    }
                    
                    // Create necessary directories
                    $directories = [
                        'uploads',
                        'uploads/avatars',
                        'uploads/posts',
                        'uploads/documents',
                        'uploads/images'
                    ];
                    
                    foreach ($directories as $dir) {
                        if (!file_exists($dir)) {
                            if (!mkdir($dir, 0777, true)) {
                                throw new Exception('Failed to create directory: ' . $dir);
                            }
                        }
                    }
                    
                    $success = 'Installation completed successfully! <a href="login.php">Login now</a>';
                    $installed = true;
                } else {
                    throw new Exception('Error executing SQL: ' . $conn->error);
                }
            }
        } catch (Exception $e) {
            $error = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <div class="container">
        <div class="form-container" style="margin-top: 2rem;">
            <h2>ChatApp Installation</h2>
            
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
            
            <?php if (!$installed): ?>
                <p>Welcome to the ChatApp installation wizard. This will set up the database and create an admin user.</p>
                
                <form method="POST" action="install.php">
                    <div class="form-group">
                        <label for="admin_username">Admin Username</label>
                        <input type="text" id="admin_username" name="admin_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Admin Email</label>
                        <input type="email" id="admin_email" name="admin_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Admin Password</label>
                        <input type="password" id="admin_password" name="admin_password" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Install ChatApp</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
