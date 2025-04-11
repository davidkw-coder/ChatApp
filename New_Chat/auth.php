<?php
/**
 * Authentication handler for ChatApp
 * Handles login, registration, and password changes
 */

require_once 'config.php';

// Get database connection
$conn = getDbConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($method) {
    case 'POST':
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        $action = isset($data['action']) ? $data['action'] : '';
        
        if ($action === 'login') {
            handleLogin($conn, $data);
        } elseif ($action === 'register') {
            handleRegistration($conn, $data);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
        break;
        
    case 'PUT':
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        $action = isset($data['action']) ? $data['action'] : '';
        
        if ($action === 'change_password') {
            handlePasswordChange($conn, $data);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
        break;
        
    default:
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        break;
}

// Handle user login
function handleLogin($conn, $data) {
    // Validate required fields
    if (empty($data['username']) || empty($data['password'])) {
        sendJsonResponse(['success' => false, 'message' => 'Username and password are required'], 400);
    }
    
    $username = $conn->real_escape_string($data['username']);
    $password = $data['password'];
    
    // Get user from database
    $stmt = $conn->prepare("SELECT id, username, email, password, bio, avatar FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid username or password'], 401);
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid username or password'], 401);
    }
    
    // Update user status to online
    $stmt = $conn->prepare("UPDATE users SET status = 'online', last_active = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    
    // Generate token
    $token = generateToken($user['id']);
    
    // Remove password from user data
    unset($user['password']);
    
    // Return success response
    sendJsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user
    ]);
}

// Handle user registration
function handleRegistration($conn, $data) {
    // Validate required fields
    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
        sendJsonResponse(['success' => false, 'message' => 'Username, email, and password are required'], 400);
    }
    
    $username = $conn->real_escape_string($data['username']);
    $email = $conn->real_escape_string($data['email']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendJsonResponse(['success' => false, 'message' => 'Username already exists'], 409);
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendJsonResponse(['success' => false, 'message' => 'Email already exists'], 409);
    }
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, status, created_at) VALUES (?, ?, ?, 'online', NOW())");
    $stmt->bind_param("sss", $username, $email, $password);
    
    if (!$stmt->execute()) {
        sendJsonResponse(['success' => false, 'message' => 'Registration failed: ' . $conn->error], 500);
    }
    
    $userId = $conn->insert_id;
    
    // Generate token
    $token = generateToken($userId);
    
    // Get user data
    $stmt = $conn->prepare("SELECT id, username, email, bio, avatar FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Return success response
    sendJsonResponse([
        'success' => true,
        'message' => 'Registration successful',
        'token' => $token,
        'user' => $user
    ]);
}

// Handle password change
function handlePasswordChange($conn, $data) {
    // Get authenticated user ID
    $userId = getAuthUserId();
    if (!$userId) {
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    // Validate required fields
    if (empty($data['current_password']) || empty($data['new_password'])) {
        sendJsonResponse(['success' => false, 'message' => 'Current password and new password are required'], 400);
    }
    
    $currentPassword = $data['current_password'];
    $newPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
    
    // Get current password from database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendJsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }
    
    $user = $result->fetch_assoc();
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        sendJsonResponse(['success' => false, 'message' => 'Current password is incorrect'], 401);
    }
    
    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $newPassword, $userId);
    
    if (!$stmt->execute()) {
        sendJsonResponse(['success' => false, 'message' => 'Password change failed: ' . $conn->error], 500);
    }
    
    // Return success response
    sendJsonResponse([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
}

// Close database connection
$conn->close();
?>

