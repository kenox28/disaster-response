<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized',
        'data' => []
    ]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $reports = [];
    $stmt = $conn->prepare('
        SELECT 
            er.id, 
            er.location, 
            er.emergency_type, 
            er.description, 
            er.status, 
            er.created_at,
            COUNT(va.id) AS assigned_count
        FROM emergency_reports er
        LEFT JOIN volunteer_assignments va ON va.request_type = "EmergencyReport" AND va.request_id = er.id
        GROUP BY er.id
        ORDER BY er.created_at DESC
    ');
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_id = $row['id'];
            
            // Get assigned volunteers
            $vol_stmt = $conn->prepare('
                SELECT v.id, v.full_name, v.skills, va.date_assigned
                FROM volunteer_assignments va
                JOIN volunteers v ON v.id = va.volunteer_id
                WHERE va.request_type = "EmergencyReport" AND va.request_id = ?
                ORDER BY va.date_assigned DESC
            ');
            $vol_stmt->bind_param('i', $report_id);
            $vol_stmt->execute();
            $vol_result = $vol_stmt->get_result();
            $assigned_volunteers = [];
            while ($vol_row = $vol_result->fetch_assoc()) {
                $assigned_volunteers[] = [
                    'id' => $vol_row['id'],
                    'full_name' => $vol_row['full_name'],
                    'skills' => $vol_row['skills'],
                    'date_assigned' => $vol_row['date_assigned']
                ];
            }
            $vol_stmt->close();
            
            $reports[] = [
                'id' => $row['id'],
                'location' => $row['location'],
                'emergency_type' => $row['emergency_type'],
                'description' => $row['description'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'assigned_count' => (int)$row['assigned_count'],
                'assigned_volunteers' => $assigned_volunteers
            ];
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Emergency reports fetched',
        'data' => $reports
    ]);
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? trim((string)$input['action']) : '';
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $status = isset($input['status']) ? trim($input['status']) : '';

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID', 'data' => []]);
        $conn->close();
        exit;
    }

    if ($action === 'delete') {
        // Remove assignments first
        $stmt = $conn->prepare('DELETE FROM volunteer_assignments WHERE request_type = "EmergencyReport" AND request_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare('DELETE FROM emergency_reports WHERE id = ?');
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Emergency report deleted', 'data' => []]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete report', 'data' => []]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    // Allowed statuses
    $allowed_statuses = ['Pending', 'In Progress', 'Resolved', 'Closed', 'Done'];
    if (!in_array($status, $allowed_statuses, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status', 'data' => []]);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare('UPDATE emergency_reports SET status = ? WHERE id = ?');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to prepare statement',
            'data' => []
        ]);
        $conn->close();
        exit;
    }

    $stmt->bind_param('si', $status, $id);
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Emergency report status updated',
            'data' => []
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update status',
            'data' => []
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Method not allowed',
    'data' => []
]);


