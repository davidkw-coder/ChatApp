<?php
/**
 * User management for ChatApp
 * Handles user profiles and active users
 */

require_once 'config.php';

// Get database connection
$conn = getDbConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($method) {
    case 'GET':
        // Get action from query string
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        if ($action === 'active_users') {
            getActiveUsers($conn);
        } else {
            getUserProfile($conn);
        }
        break;
        
    case 'POST':
        // Get data
        $action = '';
        
        // Check if it's a file upload
        if (isset($_FILES['avatar'])) {
            $action = 'upload_avatar';
            handleAvatarUpload($conn);
        } else {
            // Get JSON data
            $data = json_decode(file_get_contents('php://input'), true);
            $action = isset($data['action']) ? $data['action'] : '';
            
            if ($action === 'update_status') {
                updateUserStatus($conn, $data);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
            }
        }
        break;
        
    case 'PUT':
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        $action = isset($data['action']) ? $data['action'] : '';
        
        if ($action === 'update_profile') {
            updateUserProfile($conn, $data);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
        break;
        
    default:
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        break;
}

// Get user profile
function getUserProfile($conn) {
    // Get authenticated user ID
    $userId = getAuthUserId();
    if (!$userId) {
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    // Get user profile
    $stmt = $conn->prepare("SELECT id, username, email, bio, avatar, status, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendJsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }
    
    $user = $result->fetch_assoc();
    
    // Return user profile
    sendJsonResponse([
        'success' => true,
        'user' => $user
    ]);
}

// Get active users
function getActiveUsers($conn) {
    // Get authenticated user ID
    $userId = getAuthUserId();
    if (!$userId) {
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    // Get active users (active in the last 5 minutes)
    $stmt = $conn->prepare("
        SELECT id, username, avatar, status
        FROM users
        WHERE last_active > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY username ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    // Return active users
    sendJsonResponse([
        'success' => true,
        'users' => $users
    ]);
}

// Update user profile
function updateUserProfile($conn, $data) {
    // Get authenticated user ID
    $userId = getAuthUserId();
    if (!$userId) {
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    // Validate required fields
    if (empty($data['username']) || empty($data['email'])) {
        sendJsonResponse(['success' => false, 'message' => 'Username and email are required'], 400);
    }
    
    $username = $conn->real_escape_string($data['username']);
    $email = $conn->real_escape_string($data['email']);
    $bio = isset($data['bio']) ? $conn->real_escape_string($data['bio']) : '';
    
    // Check if username already exists (for another user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendJsonResponse(['success' => false, 'message' => 'Username already exists'], 409);
    }
    
    // Check if email already exists (for another user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendJsonResponse(['success' => false, 'message' => 'Email already exists'], 409);
    }
    
    // Update user profile
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $bio, $userId);
    
    if (!$stmt->execute()) {
        sendJsonResponse(['success' => false, 'message' => 'Failed to update profile: ' . $conn->error], 500);
    }
    
    // Return success
    sendJsonResponse([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
}

// Update user status
function updateUserStatus($conn, $data) {
    // Get authenticated user ID
    $userId = getAuthUserId();
    if (!$userId) {
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    // Validate status
    if (empty($data['status']) || !in_array($data['status'], ['online', 'offline'])) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
    }
    
    $status = $conn->real_escape_string($data['status']);
    
    // Update user status
    $stmt = $conn->prepare("UPDATE users SET status = ?, last_active = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $userId);
    
    if (!$stmt->execute()) {
        sendJsonResponse(['success' => false, 'message' => 'Failed to update status: ' . $conn->error], 500);
    }
    
    // Return success
    sendJsonResponse([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
}

// Handle avatar upload
function handleAvatarUpload($conn) {
    // Get authenticated user ID
    $userId = getAuthUserId();
    if (!$userId) {
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(['success' => false, 'message' => 'No file uploaded or upload error'], 400);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed'], 400);
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/avatars/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . $_FILES['avatar']['name'];
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $filepath)) {
        sendJsonResponse(['success' => false, 'message' => 'Failed to save file'], 500);
    }
    
    // Update user avatar in database
    $avatarUrl = 'uploads/avatars/' . $filename;
    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->bind_param("si", $avatarUrl, $userId);
    
    if (!$stmt->execute()) {
        // Delete uploaded file if database update fails
        unlink($filepath);
        sendJsonResponse(['success' => false, 'message' => 'Failed to update avatar: ' . $conn->error], 500);
    }
    
    // Return success with avatar URL
    sendJsonResponse([
        'success' => true,
        'message' => 'Avatar uploaded successfully',
        'avatar_url' => $avatarUrl
    ]);
}

// Close database connection
$conn->close();
?>

