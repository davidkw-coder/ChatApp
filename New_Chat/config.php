<?php
/**
 * Database configuration and utility functions
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chatapp');

// JWT Secret Key (change this to a secure random string)
define('JWT_SECRET', 'your_secure_jwt_secret_key');

// Application URL
define('APP_URL', 'http://localhost/chatapp');

// Session configuration
session_start();

// Add a function to optimize database queries for large message loads
function optimizeDatabaseForLargeQueries($conn) {
    // Set temporary MySQL session variables to optimize for large result sets
    $conn->query("SET SESSION max_heap_table_size = 134217728"); // 128MB
    $conn->query("SET SESSION tmp_table_size = 134217728"); // 128MB
    $conn->query("SET SESSION join_buffer_size = 8388608"); // 8MB
    $conn->query("SET SESSION sort_buffer_size = 8388608"); // 8MB
    
    return $conn;
}

// Connect to database
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    // Optimize for large queries
    $conn = optimizeDatabaseForLargeQueries($conn);
    
    return $conn;
}

// Generate JWT token
function generateToken($user_id) {
    $issuedAt = time();
    $expirationTime = $issuedAt + 60 * 60 * 24; // 24 hours
    
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'user_id' => $user_id
    ];
    
    // Encode Header
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    
    // Encode Payload
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    
    // Create Signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    // Create JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    
    return $jwt;
}

// Verify JWT token
function verifyToken($token) {
    // Split token
    $tokenParts = explode('.', $token);
    if (count($tokenParts) != 3) {
        return false;
    }
    
    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
    $signatureProvided = $tokenParts[2];
    
    // Check if token is expired
    $payloadObj = json_decode($payload);
    if (!$payloadObj || !isset($payloadObj->exp) || $payloadObj->exp < time()) {
        return false;
    }
    
    // Verify signature
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if ($base64UrlSignature !== $signatureProvided) {
        return false;
    }
    
    return $payloadObj;
}

// Get authenticated user ID from token
function getAuthUserId() {
    // Check for token in Authorization header
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
                $payload = verifyToken($token);
                if ($payload && isset($payload->user_id)) {
                    return $payload->user_id;
                }
            }
        }
    }
    
    // Check for token in session
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    return null;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Redirect to chat if already logged in
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: chat.php');
        exit;
    }
}

// Format date
function formatDate($date, $format = 'M j, Y g:i A') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

// Sanitize output
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Flash messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Get user data
function getUserData($userId = null) {
    if ($userId === null) {
        $userId = getCurrentUserId();
    }
    
    if (!$userId) {
        return null;
    }
    
    $conn = getDbConnection();
    if ($conn === false) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT id, username, email, bio, avatar, status FROM users WHERE id = ?");
    
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        return null;
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->close();
        return null;
    }
    
    $user = $result->fetch_assoc();
    $conn->close();
    
    return $user;
}

// Update user's last active time
function updateLastActive() {
    if (!isLoggedIn()) {
        return;
    }
    
    $userId = getCurrentUserId();
    $conn = getDbConnection();
    
    if ($conn === false) {
        return;
    }
    
    $stmt = $conn->prepare("UPDATE users SET last_active = NOW(), status = 'online' WHERE id = ?");
    
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        return;
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    $conn->close();
}

// Check if a user is online (active in the last 5 minutes)
function isUserOnline($lastActive) {
    if (!$lastActive) return false;
    return (time() - strtotime($lastActive)) < 300; // 5 minutes
}

// Send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
