<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

$alerts = [];

$stmt = $conn->prepare('SELECT id, title, message, severity, created_at FROM alerts ORDER BY created_at DESC');

if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Alerts fetched',
    'data' => $alerts
]);

if ($stmt) {
    $stmt->close();
}

$conn->close();


