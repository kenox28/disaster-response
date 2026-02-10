<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['volunteer_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized',
        'data' => []
    ]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'data' => []
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$full_name = isset($input['full_name']) ? trim($input['full_name']) : '';
$skills = isset($input['skills']) ? trim($input['skills']) : '';
$availability = isset($input['availability']) ? trim($input['availability']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if ($full_name === '' || $skills === '' || $availability === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Full name, skills, and availability are required',
        'data' => []
    ]);
    exit;
}

$volunteerId = (int) $_SESSION['volunteer_id'];

if ($password !== '') {
    // Password strength: at least 8 chars, one number, one special character
    if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password must be at least 8 characters and include a number and a special character',
            'data' => []
        ]);
        exit;
    }
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("
        UPDATE volunteers
        SET full_name = ?, skills = ?, availability = ?, password_hash = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to prepare statement',
            'data' => []
        ]);
        exit;
    }
    $stmt->bind_param('ssssi', $full_name, $skills, $availability, $passwordHash, $volunteerId);
} else {
    $stmt = $conn->prepare("
        UPDATE volunteers
        SET full_name = ?, skills = ?, availability = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to prepare statement',
            'data' => []
        ]);
        exit;
    }
    $stmt->bind_param('sssi', $full_name, $skills, $availability, $volunteerId);
}

if ($stmt->execute()) {
    $_SESSION['volunteer_name'] = $full_name;
    echo json_encode([
        'status' => 'success',
        'message' => 'Profile updated successfully',
        'data' => []
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update profile',
        'data' => []
    ]);
}

$stmt->close();
$conn->close();


