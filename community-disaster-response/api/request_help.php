<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'data' => []
    ]);
    exit;
}

$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
$help_type = '';
$description = '';

if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $help_type = isset($input['help_type']) ? trim($input['help_type']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
} else {
    $help_type = isset($_POST['help_type']) ? trim($_POST['help_type']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
}

if ($help_type === '' || $description === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required',
        'data' => []
    ]);
    exit;
}

// Optional image handling
$imagePath = '';

function cdr_save_help_image(string $fieldName): string
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return '';
    }
    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return '';
    }
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

    $baseDir = __DIR__ . '/../uploads/help_requests';
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0755, true);
    }

    try {
        $name = 'hr_' . bin2hex(random_bytes(8)) . '.' . $ext;
    } catch (Exception $e) {
        $name = 'hr_' . uniqid() . '.' . $ext;
    }

    $dest = $baseDir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        return '';
    }

    return 'uploads/help_requests/' . $name;
}

if (stripos($contentType, 'multipart/form-data') !== false) {
    $imagePath = cdr_save_help_image('photo');
}

if ($imagePath === '') {
    $typeLower = strtolower($help_type);
    if ($typeLower === 'medical') {
        $imagePath = 'assets/img/defaults/default_help_medical.jpg';
    } elseif ($typeLower === 'food') {
        $imagePath = 'assets/img/defaults/default_help_food.jpg';
    } elseif ($typeLower === 'rescue') {
        $imagePath = 'assets/img/defaults/default_help_rescue.jpg';
    } elseif ($typeLower === 'shelter') {
        $imagePath = 'assets/img/defaults/default_help_shelter.jpg';
    } else {
        $imagePath = 'assets/img/defaults/default_help_generic.jpg';
    }
}

$status = 'Pending';

$stmt = $conn->prepare(
    'INSERT INTO help_requests (help_type, description, status, image_path, created_at)
     VALUES (?, ?, ?, ?, NOW())'
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

$stmt->bind_param('ssss', $help_type, $description, $status, $imagePath);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to submit help request',
        'data' => []
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$requestId = $stmt->insert_id;
$stmt->close();

$createdAt = date('Y-m-d H:i:s');
$safeType = htmlspecialchars($help_type, ENT_QUOTES, 'UTF-8');
$safeDescription = nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8'));
$imageUrl = $imagePath !== '' ? '../../' . $imagePath : '';

$htmlBody = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Help Request</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;background:#f3f4f6;">
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f3f4f6;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.08);">
          <tr>
            <td style="padding:28px 24px;background:linear-gradient(135deg,#dc2626 0%,#7f1d1d 100%);color:#ffffff;text-align:left;">
              <h1 style="margin:0 0 8px;font-size:22px;line-height:1.3;">🆘 New Help Request</h1>
              <p style="margin:0;font-size:13px;opacity:0.9;">Community Disaster Response – Assistance Needed</p>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 24px 8px 24px;">
              <h2 style="margin:0 0 8px;font-size:18px;color:#111827;">' . $safeType . '</h2>
              <p style="margin:0 0 12px;font-size:13px;color:#6b7280;">Submitted at ' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</p>
              <p style="margin:0 0 4px;font-size:14px;color:#111827;"><strong>Description</strong></p>
              <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.6;">' . $safeDescription . '</p>
';
if ($imageUrl !== '') {
    $htmlBody .= '
              <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 16px;">
                <tr>
                  <td style="border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;background:#000000;">
                    <img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="Help request image" style="display:block;width:100%;height:auto;border:0;max-height:320px;object-fit:cover;">
                  </td>
                </tr>
              </table>
';
}
$htmlBody .= '
              <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 8px;">
                <tr>
                  <td style="padding:14px 14px;border-radius:10px;background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border:1px solid #bfdbfe;">
                    <p style="margin:0;font-size:13px;color:#1e3a8a;line-height:1.5;">
                      📣 <strong>Volunteer Notice:</strong> If you are available and qualified, please check this request in the system and indicate that you will help.
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

$textBody = "A new help request has been submitted:\n\nType: {$help_type}\nDate/Time: {$createdAt}\n\nDescription:\n{$description}\n";

// Broadcast to volunteers (best-effort)
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
            '🚨 New Help Request',
            $htmlBody,
            $textBody
        );
        if (!$sendRes['ok']) {
            $errors[] = $email;
        }
    }
    $vstmt->close();
}

$message = 'Help request submitted';
if (!empty($errors)) {
    $message .= '. Some email notifications could not be sent.';
}

echo json_encode([
    'status' => 'success',
    'message' => $message,
    'data' => ['id' => $requestId]
]);

$conn->close();
