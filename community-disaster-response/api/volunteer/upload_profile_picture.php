<?php
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['volunteer_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized', 'data' => []]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed', 'data' => []]);
    exit;
}

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded', 'data' => []]);
    exit;
}

$file = $_FILES['profile_picture'];
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Upload failed', 'data' => []]);
    exit;
}

// Basic validation
$maxBytes = 2 * 1024 * 1024; // 2MB
if (($file['size'] ?? 0) > $maxBytes) {
    echo json_encode(['status' => 'error', 'message' => 'File is too large (max 2MB)', 'data' => []]);
    exit;
}

$tmp = $file['tmp_name'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmp);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];
if (!isset($allowed[$mime])) {
    echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, or WEBP is allowed', 'data' => []]);
    exit;
}

$ext = $allowed[$mime];
$volunteerId = (int)$_SESSION['volunteer_id'];

$uploadDir = __DIR__ . '/../../uploads/volunteers';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

$filename = 'v' . $volunteerId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$destPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($tmp, $destPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file', 'data' => []]);
    exit;
}

// Save relative path
$relative = '../../uploads/volunteers/' . $filename; // relative to views/volunteer/ pages
$stmt = $conn->prepare('UPDATE volunteers SET profile_picture = ? WHERE id = ?');
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement', 'data' => []]);
    exit;
}
$stmt->bind_param('si', $relative, $volunteerId);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success', 'message' => 'Profile picture updated', 'data' => ['profile_picture' => $relative]]);

