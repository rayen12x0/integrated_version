<?php
require_once __DIR__ . '/../config/config.php';

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
        // Check if API key is properly configured
        if (empty(RESEND_API_KEY)) {
            // Log error but don't fail completely in development mode
            error_log("Email sending attempted but RESEND_API_KEY is not configured. Skipping email to $to with subject: $subject");

            // In development mode, we might want to simulate success to prevent breaking functionality
            if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
                error_log("In development mode - simulating email success to $to");
                return true; // Return true to maintain functionality in development
            }

            return false; // In production, return false for failed email
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.resend.com/emails",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                "from" => "noreply@connectforpeace.com",
                "to" => [$to],
                "subject" => $subject,
                "html" => $htmlContent
            ]),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . RESEND_API_KEY,
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            error_log("Email sending error: " . $error);
            return false;
        }

        if ($httpCode === 200) {
            error_log("Email sent successfully to $to. Subject: $subject. Response: $response");
            return true;
        } else {
            error_log("Email sending failed to $to. HTTP Code: $httpCode. Response: $response. Subject: $subject");
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