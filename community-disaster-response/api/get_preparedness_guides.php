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

$guides = [];

$stmt = $conn->prepare('SELECT id, title, content, category FROM preparedness_guides WHERE COALESCE(is_archived, 0) = 0 ORDER BY category ASC, id ASC');

if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $guides[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Preparedness guides fetched',
    'data' => $guides
]);

if ($stmt) {
    $stmt->close();
}

$conn->close();


