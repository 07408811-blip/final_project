<?php
session_start();

require_once "../../vendor/autoload.php";

header('Content-Type: application/json');

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$userId) {
    echo json_encode(['status' => false, 'message' => 'Not authenticated']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['status' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => false, 'message' => 'Upload error']);
    exit;
}

$maxSizeBytes = 10 * 1024 * 1024; // 10MB
if (($file['size'] ?? 0) > $maxSizeBytes) {
    echo json_encode(['status' => false, 'message' => 'File too large (max 10MB)']);
    exit;
}

$origName = $file['name'] ?? 'file';
$tmpName = $file['tmp_name'] ?? '';
if (!$tmpName || !is_uploaded_file($tmpName)) {
    echo json_encode(['status' => false, 'message' => 'Invalid upload tmp file']);
    exit;
}

$allowedExt = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'zip'];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!$ext || !in_array($ext, $allowedExt, true)) {
    echo json_encode(['status' => false, 'message' => 'File type not allowed']);
    exit;
}

$mimeType = mime_content_type($tmpName) ?: ($file['type'] ?? null);

$uploadDir = __DIR__ . '/../uploads';
$publicUploadDir = '../uploads';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        echo json_encode(['status' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

$random = bin2hex(random_bytes(16));
$storedFilename = $random . '.' . $ext;
$destination = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $storedFilename;

if (!move_uploaded_file($tmpName, $destination)) {
    echo json_encode(['status' => false, 'message' => 'Failed to move uploaded file']);
    exit;
}

echo json_encode([
    'status' => true,
    'stored_filename' => $storedFilename,
    'original_name' => $origName,
    'mime_type' => $mimeType,
    'file_size' => (int)($file['size'] ?? 0),
    'url' => $publicUploadDir . '/' . $storedFilename,
]);
exit;

