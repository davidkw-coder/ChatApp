<?php
require_once 'config.php';

// Require login
requireLogin();

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

// Get file info
$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmpPath = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];
$fileError = $file['error'];

// Validate file type
$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain'
];

if (!in_array($fileType, $allowedTypes)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

// Validate file size (5MB max)
if ($fileSize > 5 * 1024 * 1024) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
    exit;
}

// Generate unique filename
$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
$newFileName = uniqid() . '.' . $fileExtension;

// Determine upload directory based on file type
if (strpos($fileType, 'image/') === 0) {
    $uploadDir = 'uploads/images/';
} elseif (strpos($fileType, 'application/pdf') === 0) {
    $uploadDir = 'uploads/documents/';
} elseif (strpos($fileType, 'application/msword') === 0 || strpos($fileType, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') === 0) {
    $uploadDir = 'uploads/documents/';
} else {
    $uploadDir = 'uploads/other/';
}

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Upload file
$targetFilePath = $uploadDir . $newFileName;
if (move_uploaded_file($fileTmpPath, $targetFilePath)) {
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'file' => [
            'name' => $fileName,
            'path' => $targetFilePath,
            'type' => $fileType,
            'size' => $fileSize
        ]
    ]);
} else {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}
?>

