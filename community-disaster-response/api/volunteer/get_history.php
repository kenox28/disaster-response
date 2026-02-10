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

$volunteerId = (int) $_SESSION['volunteer_id'];

$history = [];

$stmt = $conn->prepare("
    SELECT id, type, description, date_submitted
    FROM volunteer_history
    WHERE volunteer_id = ?
    ORDER BY date_submitted DESC
");

if ($stmt) {
    $stmt->bind_param('i', $volunteerId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
}

echo json_encode([
    'status' => 'success',
    'message' => 'History fetched',
    'data' => $history
]);

$conn->close();


