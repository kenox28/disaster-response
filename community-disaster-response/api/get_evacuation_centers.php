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

$centers = [];

// Sort by availability: Available first, then Full, then Closed
$stmt = $conn->prepare("
    SELECT id, name, address, capacity, status 
    FROM evacuation_centers 
    ORDER BY FIELD(status, 'Available', 'Open', 'Full', 'Closed'), name ASC
");

if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $centers[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Evacuation centers fetched',
    'data' => $centers
]);

if ($stmt) {
    $stmt->close();
}

$conn->close();


