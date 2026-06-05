<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendInquiryEmail($pdo, $subject, $body)
{
    // 1. Fetch Settings
    $settings = [];
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    catch (Exception $e) {
        error_log("Mailer: Failed to fetch settings: " . $e->getMessage());
        return false;
    }

    $smtp_host = $settings['smtp_host'] ?? '';
    $smtp_user = $settings['smtp_user'] ?? '';
    $smtp_pass = $settings['smtp_pass'] ?? '';
    $smtp_port = $settings['smtp_port'] ?? 587;
    $to_email = $settings['notification_email'] ?? '';
    $from_name = $settings['email_from_name'] ?? 'Travel with IS Tours';

    if (empty($smtp_host) || empty($to_email)) {
        error_log("Mailer: SMTP Host or Notification Email not set.");
        return false; // SMTP not configured
    }

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        // Auto-detect TLS/SSL based on port
        if ($smtp_port == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port = $smtp_port;

        //Recipients
        // It's best practice to use the authenticated email as the From address
        $mail->setFrom($smtp_user, $from_name);
        $mail->addAddress($to_email);

        //Content
        $mail->isHTML(false); // Send as plain text to ensure readability
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    }
    catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
