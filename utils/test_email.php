<?php
// Test script to verify PHPMailer email functionality
require_once __DIR__ . '/vendor/autoload.php'; // Load PHPMailer via Composer
require_once __DIR__ . '/../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Create a simple test email function
function testEmailSending($recipientEmail, $recipientName = 'Test User') {
    $mail = new PHPMailer(true);

    try {
        // Server settings - Use values from config.php
        $mail->isSMTP();											
        $mail->Host       = SMTP_HOST ?? 'smtp.gmail.com';			
        $mail->SMTPAuth   = true;									
        $mail->Username   = SMTP_USERNAME ?? 'your-email@gmail.com';	
        $mail->Password   = SMTP_PASSWORD ?? 'your-app-password';	
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;			
        $mail->Port       = SMTP_PORT ?? 587;						
        $mail->SMTPDebug  = SMTP::DEBUG_SERVER; // Enable verbose debug output (set to 0 for production)

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL ?? 'noreply@connectforpeace.com', SMTP_FROM_NAME ?? 'Connect for Peace');
        $mail->addAddress($recipientEmail, $recipientName);			

        // Content
        $mail->isHTML(true);										
        $mail->Subject = 'Test Email - Connect for Peace';
        $mail->Body    = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Test Email - Connect for Peace</title>
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff;">
                    <h2 style="color: #007bff;">Hello ' . htmlspecialchars($recipientName) . '!</h2>

                    <p>This is a test email to verify that the PHPMailer integration is working correctly in your Connect for Peace application.</p>

                    <div style="background-color: white; padding: 15px; border-radius: 5px; border: 1px solid #e9ecef; margin: 15px 0;">
                        <h3 style="margin-top: 0; color: #2c3e50;">Email Configuration Test</h3>
                        <p><strong>Status:</strong> Success!</p>
                        <p><strong>Recipient:</strong> ' . htmlspecialchars($recipientEmail) . '</p>
                        <p><strong>Time:</strong> ' . date('F j, Y \a\t g:i A') . '</p>
                    </div>

                    <p style="font-style: italic;">If you received this email, your PHPMailer configuration is working properly.</p>

                    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                    <p style="font-size: 12px; color: #777;">
                        This is a test email from Connect for Peace.
                        If you believe you received this email in error, please contact our support team.
                    </p>
                </div>
            </body>
            </html>
        ';
        $mail->AltBody = 'This is a test email to verify that the PHPMailer integration is working correctly in your Connect for Peace application.';

        $mail->send();
        return ['success' => true, 'message' => 'Test email sent successfully to ' . $recipientEmail];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}

// Check if form was submitted
if ($_POST['recipient_email'] ?? false) {
    $recipientEmail = trim($_POST['recipient_email']);
    $recipientName = trim($_POST['recipient_name'] ?? 'Test User');

    if (filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $result = testEmailSending($recipientEmail, $recipientName);
    } else {
        $result = ['success' => false, 'message' => 'Invalid email address provided'];
    }
} else {
    $result = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPMailer Test - Connect for Peace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-md p-8 max-w-md w-full">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">PHPMailer Test</h1>
        
        <?php if ($result): ?>
            <div class="<?php echo $result['success'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> p-3 rounded mb-4">
                <?php echo htmlspecialchars($result['message']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label for="recipient_email" class="block text-sm font-medium text-gray-700 mb-1">Recipient Email</label>
                <input type="email" name="recipient_email" id="recipient_email" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="recipient@example.com" required>
            </div>
            
            <div>
                <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-1">Recipient Name (Optional)</label>
                <input type="text" name="recipient_name" id="recipient_name" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Test User">
            </div>
            
            <div class="text-sm text-gray-600 bg-yellow-50 p-3 rounded border border-yellow-200">
                <p class="font-medium mb-1">⚠️ Important Configuration Note:</p>
                <p>Before testing, ensure your <code>config/config.php</code> has properly configured SMTP settings:</p>
                <ul class="mt-2 space-y-1 list-disc list-inside">
                    <li><code>SMTP_HOST</code> - SMTP server (e.g., smtp.gmail.com)</li>
                    <li><code>SMTP_USERNAME</code> - Your email address</li>
                    <li><code>SMTP_PASSWORD</code> - Your app password (not regular password)</li>
                    <li><code>SMTP_PORT</code> - Port number (usually 587 for STARTTLS)</li>
                </ul>
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Send Test Email
            </button>
        </form>
        
        <div class="mt-6 text-sm text-gray-600">
            <h3 class="font-medium mb-2">Current SMTP Configuration:</h3>
            <ul class="space-y-1">
                <li><strong>Host:</strong> <?php echo htmlspecialchars(SMTP_HOST ?? 'NOT SET'); ?></li>
                <li><strong>Username:</strong> <?php echo htmlspecialchars(SMTP_USERNAME ?? 'NOT SET'); ?></li>
                <li><strong>Port:</strong> <?php echo htmlspecialchars(SMTP_PORT ?? 'NOT SET'); ?></li>
                <li><strong>From Email:</strong> <?php echo htmlspecialchars(SMTP_FROM_EMAIL ?? 'NOT SET'); ?></li>
            </ul>
        </div>
    </div>
</body>
</html>