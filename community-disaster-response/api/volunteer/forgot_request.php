<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed', 'data' => []]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Valid email is required', 'data' => []]);
    exit;
}

// Ensure account exists
$stmt = $conn->prepare('SELECT id, full_name FROM volunteers WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$v = $res->fetch_assoc();
$stmt->close();

if (!$v) {
    echo json_encode(['status' => 'error', 'message' => 'Email not found', 'data' => []]);
    exit;
}

// Delete existing reset requests for email
$stmt = $conn->prepare('DELETE FROM volunteer_password_resets WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->close();

// Create OTP
$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

$stmt = $conn->prepare('INSERT INTO volunteer_password_resets (email, otp_code, otp_expires_at) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $email, $otp, $expires);
if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Failed to create reset request', 'data' => []]);
    exit;
}
$stmt->close();

// Create HTML email with white and red theme
$htmlBody = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 40px 20px;">
    <table role="presentation" style="width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); padding: 40px 30px; text-align: center;">
                <h1 style="margin: 0; font-family: \'Poppins\', sans-serif; font-size: 28px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">
                    🚨 Password Reset Request
                </h1>
            </td>
        </tr>
        
        <!-- Content -->
        <tr>
            <td style="padding: 40px 30px;">
                <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.6; color: #1f2937;">
                    Hello <strong>' . htmlspecialchars($v['full_name'] ?? 'User') . '</strong>,
                </p>
                
                <p style="margin: 0 0 30px; font-size: 16px; line-height: 1.6; color: #4b5563;">
                    We received a request to reset your password. Please use the following One-Time Password (OTP) to complete the process:
                </p>
                
                <!-- OTP Box -->
                <table role="presentation" style="width: 100%; margin: 0 0 30px;">
                    <tr>
                        <td style="background: linear-gradient(135deg, rgba(220, 38, 38, 0.05) 0%, rgba(153, 27, 27, 0.05) 100%); border: 2px dashed #dc2626; border-radius: 12px; padding: 30px; text-align: center;">
                            <div style="font-family: \'Courier New\', monospace; font-size: 42px; font-weight: 800; color: #dc2626; letter-spacing: 8px; margin: 0;">
                                ' . htmlspecialchars($otp) . '
                            </div>
                        </td>
                    </tr>
                </table>
                
                <!-- Warning Box -->
                <table role="presentation" style="width: 100%; margin: 0 0 30px;">
                    <tr>
                        <td style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; border-radius: 8px; padding: 20px;">
                            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #92400e;">
                                ⏰ <strong>Important:</strong> This OTP will expire in <strong>10 minutes</strong>. Please use it promptly.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.6; color: #4b5563;">
                    If you didn\'t request this password reset, please ignore this email or contact support if you have concerns.
                </p>
                
                <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #4b5563;">
                    Stay safe,<br>
                    <strong style="color: #dc2626;">Community Disaster Resilience Team</strong>
                </p>
            </td>
        </tr>
        
        <!-- Footer -->
        <tr>
            <td style="background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); padding: 30px; text-align: center; border-top: 1px solid rgba(220, 38, 38, 0.1);">
                <p style="margin: 0 0 10px; font-size: 13px; color: #6b7280; line-height: 1.6;">
                    This is an automated message. Please do not reply to this email.
                </p>
                <p style="margin: 0; font-size: 12px; color: #9ca3af;">
                    © ' . date('Y') . ' Community Disaster Resilience System. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
    
    <!-- Spacer for mobile -->
    <div style="height: 40px;"></div>
</body>
</html>
';

// Plain text version
$textBody = 'Hello ' . ($v['full_name'] ?? 'User') . ',

We received a request to reset your password. Please use the following One-Time Password (OTP):

OTP: ' . $otp . '

This code will expire in 10 minutes.

If you didn\'t request this password reset, please ignore this email.

Stay safe,
Community Disaster Resilience Team

---
This is an automated message. Please do not reply to this email.
© ' . date('Y') . ' Community Disaster Resilience System. All rights reserved.';

// Send OTP email
$send = cdr_send_mail(
    $email,
    $v['full_name'] ?? $email,
    'Password Reset OTP - Community Disaster Resilience',
    $htmlBody,
    $textBody
);

if (!$send['ok']) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email: ' . ($send['error'] ?? ''), 'data' => []]);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'OTP sent to your email', 'data' => ['email' => $email]]);