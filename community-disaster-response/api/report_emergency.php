<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php';

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'data' => []
    ]);
    exit;
}

// Support both JSON (older clients) and multipart/form-data (with photo)
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
$location = '';
$emergency_type = '';
$description = '';

if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $location = isset($input['location']) ? trim($input['location']) : '';
    $emergency_type = isset($input['emergency_type']) ? trim($input['emergency_type']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
} else {
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $emergency_type = isset($_POST['emergency_type']) ? trim($_POST['emergency_type']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
}

if ($location === '' || $emergency_type === '' || $description === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required',
        'data' => []
    ]);
    exit;
}

// Optional image handling
$imagePath = '';

/**
 * Save uploaded emergency image (if any).
 *
 * @return string relative path (e.g. "uploads/emergencies/abc.jpg") or empty string on failure
 */
function cdr_save_emergency_image(string $fieldName): string
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return '';
    }
    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return '';
    }

    // Max ~2MB
    $maxBytes = 2 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        return '';
    }

    $tmp = $file['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return '';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    if (!isset($allowed[$mime])) {
        return '';
    }
    $ext = $allowed[$mime];

    $baseDir = __DIR__ . '/../uploads/emergencies';
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0755, true);
    }

    try {
        $name = 'er_' . bin2hex(random_bytes(8)) . '.' . $ext;
    } catch (Exception $e) {
        $name = 'er_' . uniqid() . '.' . $ext;
    }

    $dest = $baseDir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        return '';
    }

    // Path relative to project root
    return 'uploads/emergencies/' . $name;
}

// Try to save uploaded image (only for multipart/form-data)
if (stripos($contentType, 'multipart/form-data') !== false) {
    $imagePath = cdr_save_emergency_image('photo');
}

// Fallback to default image if no upload or upload failed
if ($imagePath === '') {
    $typeLower = strtolower($emergency_type);
    if ($typeLower === 'earthquake') {
        $imagePath = 'assets/img/defaults/default_earthquake.jpg';
    } elseif ($typeLower === 'flood') {
        $imagePath = 'assets/img/defaults/default_flood.jpg';
    } elseif ($typeLower === 'fire') {
        $imagePath = 'assets/img/defaults/default_fire.jpg';
    } elseif ($typeLower === 'typhoon') {
        $imagePath = 'assets/img/defaults/default_typhoon.jpg';
    } else {
        $imagePath = 'assets/img/defaults/default_emergency.jpg';
    }
}

$status = 'Pending';

$stmt = $conn->prepare(
    'INSERT INTO emergency_reports (location, emergency_type, description, status, image_path, created_at)
     VALUES (?, ?, ?, ?, ?, NOW())'
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare statement',
        'data' => []
    ]);
    exit;
}

$stmt->bind_param('sssss', $location, $emergency_type, $description, $status, $imagePath);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to submit report',
        'data' => []
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$reportId = $stmt->insert_id;
$stmt->close();

// Email broadcast to all volunteers (best-effort, non-fatal)
$createdAt = date('Y-m-d H:i:s');
$safeLocation = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
$safeType = htmlspecialchars($emergency_type, ENT_QUOTES, 'UTF-8');
$safeDescription = nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8'));
$imageUrl = $imagePath !== '' ? '../../' . $imagePath : '';

$htmlBody = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Emergency Reported</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;background:#f3f4f6;">
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f3f4f6;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.08);">
          <tr>
            <td style="padding:28px 24px;background:linear-gradient(135deg,#dc2626 0%,#7f1d1d 100%);color:#ffffff;text-align:left;">
              <h1 style="margin:0 0 8px;font-size:22px;line-height:1.3;">🚨 New Emergency Reported</h1>
              <p style="margin:0;font-size:13px;opacity:0.9;">Community Disaster Response – Real-time Alert</p>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 24px 8px 24px;">
              <h2 style="margin:0 0 8px;font-size:18px;color:#111827;">' . $safeType . '</h2>
              <p style="margin:0 0 12px;font-size:13px;color:#6b7280;">Reported at ' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</p>
              <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 16px;">
                <tr>
                  <td style="padding:12px 14px;border-radius:10px;background:linear-gradient(135deg,#fef2f2 0%,#fee2e2 100%);border:1px solid #fecaca;">
                    <p style="margin:0;font-size:14px;color:#7f1d1d;"><strong>Location:</strong> ' . $safeLocation . '</p>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 4px;font-size:14px;color:#111827;"><strong>Description</strong></p>
              <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.6;">' . $safeDescription . '</p>
';
if ($imageUrl !== '') {
    $htmlBody .= '
              <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 16px;">
                <tr>
                  <td style="border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;background:#000000;">
                    <img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="Emergency image" style="display:block;width:100%;height:auto;border:0;max-height:320px;object-fit:cover;">
                  </td>
                </tr>
              </table>
';
}
$htmlBody .= '
              <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 8px;">
                <tr>
                  <td style="padding:14px 14px;border-radius:10px;background:linear-gradient(135deg,#fefce8 0%,#fef3c7 100%);border:1px solid #fde68a;">
                    <p style="margin:0;font-size:13px;color:#92400e;line-height:1.5;">
                      ⚠️ <strong>Action Required:</strong> Please review this report in the Community Disaster Response system and coordinate appropriate actions if you are available to help.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 24px 20px 24px;border-top:1px solid #e5e7eb;background:#f9fafb;">
              <p style="margin:0 0 4px;font-size:12px;color:#6b7280;">This notification was sent by <strong>Community Disaster Response</strong>.</p>
              <p style="margin:0;font-size:11px;color:#9ca3af;">Please do not reply directly to this email. © ' . date('Y') . ' Community Disaster Response.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

$textBody = "A new emergency has been reported:\n\nType: {$emergency_type}\nLocation: {$location}\nDate/Time: {$createdAt}\n\nDescription:\n{$description}\n";

// Fetch all volunteers with emails
$vstmt = $conn->prepare("SELECT full_name, email FROM volunteers WHERE email IS NOT NULL AND email <> ''");
$errors = [];
if ($vstmt && $vstmt->execute()) {
    $vres = $vstmt->get_result();
    while ($row = $vres->fetch_assoc()) {
        $email = $row['email'];
        $name = $row['full_name'] ?: $email;
        $sendRes = cdr_send_mail(
            $email,
            $name,
            '🚨 New Emergency Reported',
            $htmlBody,
            $textBody
        );
        if (!$sendRes['ok']) {
            $errors[] = $email;
        }
    }
    $vstmt->close();
}

$message = 'Emergency report submitted';
if (!empty($errors)) {
    $message .= '. Some email notifications could not be sent.';
}

echo json_encode([
    'status' => 'success',
    'message' => $message,
    'data' => ['id' => $reportId]
]);

$conn->close();
