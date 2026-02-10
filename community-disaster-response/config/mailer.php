<?php
/**
 * Simple PHPMailer wrapper.
 *
 * IMPORTANT:
 * - Install PHPMailer either via Composer (vendor/autoload.php) OR
 *   place PHPMailer source in: community-disaster-response/assets/libs/phpmailer/src/
 *     - PHPMailer.php
 *     - SMTP.php
 *     - Exception.php
 *
 * Configure SMTP below for real email sending.
 */

function cdr_load_phpmailer(): bool {
    // Composer install
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        require_once $composerAutoload;
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    // Manual install
    $base = __DIR__ . '/../assets/libs/phpmailer/src/';
    if (file_exists($base . 'PHPMailer.php') && file_exists($base . 'SMTP.php') && file_exists($base . 'Exception.php')) {
        require_once $base . 'Exception.php';
        require_once $base . 'PHPMailer.php';
        require_once $base . 'SMTP.php';
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    return false;
}

/**
 * @return array{ok:bool,error?:string}
 */
function cdr_send_mail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): array {
    if (!cdr_load_phpmailer()) {
        return [
            'ok' => false,
            'error' => 'PHPMailer is not installed. Please install via Composer or copy PHPMailer into assets/libs/phpmailer/src/.'
        ];
    }

    try {
        // PHPMailer class is loaded dynamically by cdr_load_phpmailer()
        $class = 'PHPMailer\\PHPMailer\\PHPMailer';
        $mail = new $class(true);

        // ===== SMTP CONFIG (EDIT THESE) =====
        // For school projects using Gmail, you may need an App Password.
        // $mail->isSMTP();
        // $mail->Host = 'smtp.gmail.com';
        // $mail->SMTPAuth = true;
        // $mail->Username = 'your-email@gmail.com';
        // $mail->Password = 'your-app-password';
        // $mail->SMTPSecure = $class::ENCRYPTION_STARTTLS;
        // $mail->Port = 587;

        // If you have a local SMTP server, configure it above.

        $mail->setFrom('no-reply@example.com', 'Community Disaster Response');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);

        $mail->send();
        return ['ok' => true];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

