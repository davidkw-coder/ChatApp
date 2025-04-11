<?php
require_once 'config.php';

// Require login
requireLogin();

// Get current user data
$user = getUserData();

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    // Validate input
    if (empty($username) || empty($email)) {
        $error = 'Username and email are required';
    } else {
        $conn = getDbConnection();
        
        // Check if username already exists (for another user)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Check if email already exists (for another user)
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email already exists';
            } else {
                // Update profile
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?");
                $stmt->bind_param("sssi", $username, $email, $bio, $user['id']);
                
                if ($stmt->execute()) {
                    $success = 'Profile updated successfully';
                    
                    // Update session username
                    $_SESSION['username'] = $username;
                    
                    // Refresh user data
                    $user = getUserData();
                } else {
                    $error = 'Failed to update profile: ' . $conn->error;
                }
            }
        }
        
        $conn->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $conn = getDbConnection();
        
        // Get current password from database
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'User not found';
        } else {
            $userData = $result->fetch_assoc();
            
            // Verify current password
            if (!password_verify($current_password, $userData['password'])) {
                $error = 'Current password is incorrect';
            } else {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user['id']);
                
                if ($stmt->execute()) {
                    $success = 'Password changed successfully';
                } else {
                    $error = 'Failed to change password: ' . $conn->error;
                }
            }
        }
        
        $conn->close();
    }
}

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    // Check if file was uploaded without errors
    if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Get file info
        $fileName = $_FILES['avatar']['name'];
        $fileType = $_FILES['avatar']['type'];
        $fileTmpName = $_FILES['avatar']['tmp_name'];
        $fileError = $_FILES['avatar']['error'];
        $fileSize = $_FILES['avatar']['size'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileType, $allowedTypes)) {
            $error = 'Only JPG, PNG, and GIF files are allowed';
        } elseif ($fileSize > 2000000) { // 2MB max
            $error = 'File size must be less than 2MB';
        } else {
            // Generate unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '.' . $fileExtension;
            $targetFilePath = $uploadDir . $newFileName;
            
            // Upload file
            if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                // Update avatar in database
                $conn = getDbConnection();
                $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->bind_param("si", $targetFilePath, $user['id']);
                
                if ($stmt->execute()) {
                    $success = 'Avatar uploaded successfully';
                    
                    // Delete old avatar if it exists and is not the default
                    if (!empty($user['avatar']) && file_exists($user['avatar']) && $user['avatar'] !== 'uploads/default-avatar.png') {
                        unlink($user['avatar']);
                    }
                    
                    // Refresh user data
                    $user = getUserData();
                } else {
                    $error = 'Failed to update avatar: ' . $conn->error;
                }
                
                $conn->close();
            } else {
                $error = 'Failed to upload file';
            }
        }
    } else {
        $error = 'Error uploading file: ' . $_FILES['avatar']['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ChatApp</title>
    <link rel="stylesheet" href="styles.php">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="form-container profile-container">
            <h2>Your Profile</h2>
            
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
            
            <div class="profile-avatar">
                <img src="<?php echo !empty($user['avatar']) ? h($user['avatar']) : 'uploads/default-avatar.png'; ?>" alt="Your avatar">
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="avatar" class="btn btn-secondary btn-sm">Change Avatar</label>
                        <input type="file" id="avatar" name="avatar" style="display: none;" onchange="this.form.submit()">
                    </div>
                </form>
            </div>
            
            <form method="POST" action="profile.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo h($user['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo h($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="3"><?php echo h($user['bio'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
            
            <div class="password-change">
                <h3>Change Password</h3>
                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn btn-secondary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>

