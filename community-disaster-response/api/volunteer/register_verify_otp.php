<?php
header('Content-Type: application/json');

session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/mailer.php';

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
$email = isset($input['email']) ? trim($input['email']) : '';
$otp   = isset($input['otp']) ? trim($input['otp']) : '';

if ($email === '' || $otp === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and OTP are required',
        'data' => []
    ]);
    exit;
}

$now = new DateTime();

// Find pending registration
$stmt = $conn->prepare('
    SELECT * FROM volunteer_pending_registrations
    WHERE email = ? AND otp_code = ? AND otp_expires_at >= ?
    LIMIT 1
');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement', 'data' => []]);
    exit;
}
$currentTime = $now->format('Y-m-d H:i:s');
$stmt->bind_param('sss', $email, $otp, $currentTime);
$stmt->execute();
$result = $stmt->get_result();
$pending = $result->fetch_assoc();
$stmt->close();

if (!$pending) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired OTP',
        'data' => []
    ]);
    exit;
}

// Move to volunteers table
$username     = $pending['username'];
$passwordHash = $pending['password_hash'];
$fullName     = $pending['full_name'];
$skills       = $pending['skills'];
$availability = $pending['availability'];
$gender       = $pending['gender'] ?? null;
$birthday     = $pending['birthday'] ?? null;
$age          = isset($pending['age']) ? (int)$pending['age'] : 0;
$status       = 'Pending';

$stmt = $conn->prepare('
    INSERT INTO volunteers (username, password_hash, email, full_name, skills, availability, gender, birthday, age, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare insert', 'data' => []]);
    exit;
}
$stmt->bind_param('ssssssssis', $username, $passwordHash, $email, $fullName, $skills, $availability, $gender, $birthday, $age, $status);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to create volunteer account', 'data' => []]);
    exit;
}
$newId = $stmt->insert_id;
$stmt->close();

// Remove pending record
$stmt = $conn->prepare('DELETE FROM volunteer_pending_registrations WHERE id = ?');
$stmt->bind_param('i', $pending['id']);
$stmt->execute();
$stmt->close();

// Create modern HTML confirmation email
$htmlBody = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Registration Confirmed</title>
</head>
<body style="margin:0; padding:0; font-family:\'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif; background: linear-gradient(135deg,#f3f4f6 0%,#e5e7eb 100%); padding:40px 20px;">
    <table role="presentation" style="width:100%; max-width:600px; margin:0 auto; background:#fff; border-radius:20px; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.1);">
        <tr>
            <td style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); padding:40px 30px; text-align:center;">
                <h1 style="margin:0; font-family: \'Poppins\', sans-serif; font-size:28px; font-weight:700; color:#fff;">
                    ✅ Volunteer Registration Confirmed
                </h1>
            </td>
        </tr>
        <tr>
            <td style="padding:40px 30px;">
                <p style="margin:0 0 20px; font-size:16px; line-height:1.6; color:#1f2937;">
                    Hello <strong>' . htmlspecialchars($fullName) . '</strong>,
                </p>
                <p style="margin:0 0 30px; font-size:16px; line-height:1.6; color:#4b5563;">
                    Your volunteer registration has been successfully verified! Your account is now created and is <strong>pending admin approval</strong>.
                </p>

                <table role="presentation" style="width:100%; margin:0 0 30px;">
                    <tr>
                        <td style="background: linear-gradient(135deg, rgba(16,185,129,0.05) 0%, rgba(4,120,87,0.05) 100%); border:2px dashed #10b981; border-radius:12px; padding:30px; text-align:center;">
                            <div style="font-family:\'Courier New\', monospace; font-size:20px; font-weight:700; color:#047857; margin:0;">
                                Username: ' . htmlspecialchars($username) . '
                            </div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" style="width:100%; margin:0 0 30px;">
                    <tr>
                        <td style="background: rgba(59,130,246,0.1); border-left:4px solid #3b82f6; border-radius:8px; padding:20px;">
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#1e3a8a;">
                                ⏳ Your account will be reviewed by an administrator. You will receive a notification once approved.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:0 0 20px; font-size:16px; line-height:1.6; color:#4b5563;">
                    Thank you for volunteering! We appreciate your willingness to contribute to the community.
                </p>

                <p style="margin:0; font-size:16px; line-height:1.6; color:#4b5563;">
                    Stay safe,<br>
                    <strong style="color:#10b981;">Community Disaster Resilience Team</strong>
                </p>
            </td>
        </tr>
        <tr>
            <td style="background: linear-gradient(135deg,#f9fafb 0%,#f3f4f6 100%); padding:30px; text-align:center; border-top:1px solid rgba(16,185,129,0.1);">
                <p style="margin:0 0 10px; font-size:13px; color:#6b7280; line-height:1.6;">
                    This is an automated message. Please do not reply to this email.
                </p>
                <p style="margin:0; font-size:12px; color:#9ca3af;">
                    © ' . date('Y') . ' Community Disaster Resilience System. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
    <div style="height:40px;"></div>
</body>
</html>
';

// Plain text version
$textBody = 'Hello ' . $fullName . ',

Your volunteer registration has been verified! Your account is now created and pending admin approval.

Username: ' . $username . '

Your account will be reviewed by an administrator. You will receive a notification once approved.

Thank you for volunteering!

Stay safe,
Community Disaster Resilience Team

---
This is an automated message. Please do not reply to this email.
© ' . date('Y') . ' Community Disaster Resilience System. All rights reserved.';

// Send confirmation email
cdr_send_mail(
    $email,
    $fullName,
    'Volunteer Registration Confirmed - Community Disaster Resilience',
    $htmlBody,
    $textBody
);

echo json_encode([
    'status' => 'success',
    'message' => 'Registration completed. Your account is pending admin approval.',
    'data' => [
        'id' => $newId,
        'username' => $username
    ]
]);
