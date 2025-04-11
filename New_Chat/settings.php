<?php
require_once 'config.php';

// Require login
requireLogin();

// Get current user data
$user = getUserData();
if (!$user) {
    // If user data can't be retrieved, redirect to login
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Handle notification settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $soundNotifications = isset($_POST['sound_notifications']) ? 1 : 0;
    
    $conn = getDbConnection();
    
    if ($conn === false) {
        $error = 'Database connection failed';
    } else {
        // Check if settings exist
        $stmt = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
        
        if ($stmt === false) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update settings
                $stmt = $conn->prepare("
                    UPDATE user_settings 
                    SET email_notifications = ?, sound_notifications = ? 
                    WHERE user_id = ?
                ");
                
                if ($stmt === false) {
                    $error = 'Database error: ' . $conn->error;
                } else {
                    $stmt->bind_param("iii", $emailNotifications, $soundNotifications, $user['id']);
                    
                    if ($stmt->execute()) {
                        $success = 'Notification settings updated successfully';
                    } else {
                        $error = 'Failed to update notification settings: ' . $conn->error;
                    }
                }
            } else {
                // Insert settings
                $stmt = $conn->prepare("
                    INSERT INTO user_settings (user_id, email_notifications, sound_notifications) 
                    VALUES (?, ?, ?)
                ");
                
                if ($stmt === false) {
                    $error = 'Database error: ' . $conn->error;
                } else {
                    $stmt->bind_param("iii", $user['id'], $emailNotifications, $soundNotifications);
                    
                    if ($stmt->execute()) {
                        $success = 'Notification settings updated successfully';
                    } else {
                        $error = 'Failed to update notification settings: ' . $conn->error;
                    }
                }
            }
        }
        
        $conn->close();
    }
}

// Handle privacy settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_privacy'])) {
    $showStatus = isset($_POST['show_status']) ? 1 : 0;
    $showLastSeen = isset($_POST['show_last_seen']) ? 1 : 0;
    
    $conn = getDbConnection();
    
    if ($conn === false) {
        $error = 'Database connection failed';
    } else {
        // Check if settings exist
        $stmt = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
        
        if ($stmt === false) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update settings
                $stmt = $conn->prepare("
                    UPDATE user_settings 
                    SET show_status = ?, show_last_seen = ? 
                    WHERE user_id = ?
                ");
                
                if ($stmt === false) {
                    $error = 'Database error: ' . $conn->error;
                } else {
                    $stmt->bind_param("iii", $showStatus, $showLastSeen, $user['id']);
                    
                    if ($stmt->execute()) {
                        $success = 'Privacy settings updated successfully';
                    } else {
                        $error = 'Failed to update privacy settings: ' . $conn->error;
                    }
                }
            } else {
                // Insert settings
                $stmt = $conn->prepare("
                    INSERT INTO user_settings (user_id, show_status, show_last_seen) 
                    VALUES (?, ?, ?)
                ");
                
                if ($stmt === false) {
                    $error = 'Database error: ' . $conn->error;
                } else {
                    $stmt->bind_param("iii", $user['id'], $showStatus, $showLastSeen);
                    
                    if ($stmt->execute()) {
                        $success = 'Privacy settings updated successfully';
                    } else {
                        $error = 'Failed to update privacy settings: ' . $conn->error;
                    }
                }
            }
        }
        
        $conn->close();
    }
}

// Get user settings
$conn = getDbConnection();
$settings = [];

if ($conn !== false) {
    $stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $settings = $result->fetch_assoc();
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container settings-container">
        <h2>Settings</h2>
        
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
        
        <div class="settings-section">
            <h3>Notification Settings</h3>
            <form method="POST" action="settings.php">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="email_notifications" <?php echo isset($settings['email_notifications']) && $settings['email_notifications'] ? 'checked' : ''; ?>>
                        Email Notifications
                    </label>
                    <p class="form-text">Receive email notifications for new messages and friend requests</p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="sound_notifications" <?php echo isset($settings['sound_notifications']) && $settings['sound_notifications'] ? 'checked' : ''; ?>>
                        Sound Notifications
                    </label>
                    <p class="form-text">Play sound when new messages are received</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_notifications" class="btn btn-primary">Save Notification Settings</button>
                </div>
            </form>
        </div>
        
        <div class="settings-section">
            <h3>Privacy Settings</h3>
            <form method="POST" action="settings.php">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="show_status" <?php echo isset($settings['show_status']) && $settings['show_status'] ? 'checked' : ''; ?>>
                        Show Online Status
                    </label>
                    <p class="form-text">Allow others to see when you are online</p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="show_last_seen" <?php echo isset($settings['show_last_seen']) && $settings['show_last_seen'] ? 'checked' : ''; ?>>
                        Show Last Seen
                    </label>
                    <p class="form-text">Allow others to see when you were last active</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_privacy" class="btn btn-primary">Save Privacy Settings</button>
                </div>
            </form>
        </div>
        
        <div class="settings-section">
            <h3>Account Settings</h3>
            <p>Manage your account settings and profile information.</p>
            <div class="form-actions">
                <a href="profile.php" class="btn btn-secondary">Go to Profile</a>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>
