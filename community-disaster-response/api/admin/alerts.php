<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $alerts = [];
    $stmt = $conn->prepare('SELECT id, title, message, severity, created_at FROM alerts ORDER BY created_at DESC');
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Alerts fetched',
        'data' => $alerts
    ]);
    $conn->close();
    exit;
}

// Simple create alert (title, message, severity)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = isset($input['title']) ? trim($input['title']) : '';
    $message = isset($input['message']) ? trim($input['message']) : '';
    $severity = isset($input['severity']) ? trim($input['severity']) : '';

    if ($title === '' || $message === '' || $severity === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required'
        ]);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO alerts (title, message, severity, created_at) VALUES (?, ?, ?, NOW())');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to prepare statement'
        ]);
        $conn->close();
        exit;
    }

    $stmt->bind_param('sss', $title, $message, $severity);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Alert created'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create alert'
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Method not allowed'
]);


