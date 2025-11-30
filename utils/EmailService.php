<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/vendor/autoload.php'; // Load PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {

    public static function sendReminderEmail($userEmail, $userName, $itemTitle, $itemType, $itemDate, $itemLocation) {
        $subject = "Reminder: Upcoming {$itemType} - {$itemTitle}";
        $htmlContent = self::formatEmailTemplate($userName, $itemTitle, $itemType, $itemDate, $itemLocation);

        return self::sendEmail($userEmail, $subject, $htmlContent);
    }

    public static function sendNotificationEmail($userEmail, $userName, $subject, $message, $actionLink = null, $actionText = null) {
        $htmlContent = self::formatNotificationTemplate($userName, $subject, $message, $actionLink, $actionText);
        return self::sendEmail($userEmail, $subject, $htmlContent);
    }

    public static function sendEmail($to, $subject, $htmlContent) {
        $mail = new PHPMailer(true);

        try {
            // Server settings - adjust these according to your email configuration
            // Enable verbose debug output (set to 0 for production)
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = SMTP_HOST ?? 'smtp.gmail.com';          // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = SMTP_USERNAME ?? 'your-email@gmail.com'; // SMTP username
            $mail->Password   = SMTP_PASSWORD ?? 'your-app-password';   // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
            $mail->Port       = SMTP_PORT ?? 587;                       // TCP port to connect to

            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL ?? 'noreply@connectforpeace.com', 'Connect for Peace');
            $mail->addAddress($to);                                     // Add recipient

            // Content
            $mail->isHTML(true);                                        // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $htmlContent;
            $mail->AltBody = strip_tags($htmlContent);                  // Plain text body

            $mail->send();
            error_log("Email sent successfully to $to. Subject: $subject");
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed to $to. Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function formatEmailTemplate($userName, $itemTitle, $itemType, $itemDate, $itemLocation) {
        $formattedDate = date('F j, Y \a\t g:i A', strtotime($itemDate));

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Reminder: Upcoming ' . $itemType . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff;">
                <h2 style="color: #007bff;">Hello ' . htmlspecialchars($userName) . '!</h2>

                <p>This is a friendly reminder about an upcoming ' . htmlspecialchars($itemType) . ':</p>

                <div style="background-color: white; padding: 15px; border-radius: 5px; border: 1px solid #e9ecef; margin: 15px 0;">
                    <h3 style="margin-top: 0; color: #2c3e50;">' . htmlspecialchars($itemTitle) . '</h3>
                    <p><strong>Date & Time:</strong> ' . $formattedDate . '</p>';

        if ($itemLocation) {
            $html .= '<p><strong>Location:</strong> ' . htmlspecialchars($itemLocation) . '</p>';
        }

        $html .= '
                    <p><strong>Type:</strong> ' . ucfirst(htmlspecialchars($itemType)) . '</p>
                </div>

                <p style="font-style: italic;">We hope to see you there!</p>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                <p style="font-size: 12px; color: #777;">
                    This is an automated reminder from Connect for Peace.
                    If you believe you received this email in error, please contact our support team.
                </p>
            </div>
        </body>
        </html>';

        return $html;
    }

    public static function formatNotificationTemplate($userName, $subject, $message, $actionLink = null, $actionText = null) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($subject) . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff;">
                <h2 style="color: #007bff;">Hello ' . htmlspecialchars($userName) . '!</h2>

                <p>' . $message . '</p>';  // Treat message as already-formed HTML (but ensure it's properly escaped by callers)

        if ($actionLink && $actionText) {
            $html .= '<div style="margin: 20px 0;">
                        <a href="' . $actionLink . '" style="background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block;">' . $actionText . '</a>
                      </div>';
        }

        $html .= '
                <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                <p style="font-size: 12px; color: #777;">
                    This is an automated notification from Connect for Peace.
                    If you believe you received this email in error, please contact our support team.
                </p>
            </div>
        </body>
        </html>';

        return $html;
    }
}
?>