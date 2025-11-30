<?php
// Cron job script to process and send due reminders
// This script should be executed via cron job (e.g., every 15 minutes)

error_log("Cron job started: Processing reminders");

require_once '../config/config.php';
require_once '../model/reminder.php';
require_once '../model/notification.php';
require_once '../utils/EmailService.php';

try {
    // Initialize database connection
    $conn = Config::getConnexion();
    error_log("Database connection established");

    // Instantiate models
    $reminderModel = new Reminder($conn);
    $notificationModel = new Notification($conn);

    // Get due reminders
    $dueReminders = $reminderModel->getDueReminders();
    error_log("Found " . count($dueReminders) . " due reminders to process");

    $processedCount = 0;
    $successfulCount = 0;
    $failedCount = 0;

    // Process each due reminder
    foreach ($dueReminders as $reminder) {
        $userId = $reminder['user_id'];
        $userEmail = $reminder['user_email'];
        $userName = $reminder['user_name'];
        $itemType = $reminder['item_type'];
        $itemTitle = $reminder['item_title'];
        $itemDate = $reminder['item_datetime'];
        $itemLocation = $reminder['item_location'];
        $reminderType = $reminder['reminder_type'];
        $reminderId = $reminder['id'];

        error_log("Processing reminder ID: {$reminderId} for user {$userId}, item {$itemTitle}");

        try {
            $emailSent = false;
            $notificationSent = false;

            // Send email if requested
            if ($reminderType === 'email' || $reminderType === 'both') {
                error_log("Sending email reminder to {$userEmail} for {$itemTitle}");
                $emailResult = EmailService::sendReminderEmail($userEmail, $userName, $itemTitle, $itemType, $itemDate, $itemLocation);
                $emailSent = $emailResult;
                error_log("Email result for reminder {$reminderId}: " . ($emailResult ? "SUCCESS" : "FAILED"));
            }

            // Create in-app notification if requested
            if ($reminderType === 'in_app' || $reminderType === 'both') {
                error_log("Creating in-app notification for user {$userId} for {$itemTitle}");
                $notificationMessage = "Reminder: Upcoming {$itemType} - {$itemTitle}";
                $notificationData = [
                    'user_id' => $userId,
                    'type' => 'reminder',
                    'message' => $notificationMessage,
                    'related_id' => $reminder['item_id']
                ];
                $result = $notificationModel->create($notificationData);
                $notificationSent = $result;
                error_log("Notification result for reminder {$reminderId}: " . ($result ? "SUCCESS" : "FAILED"));
            }

            // Mark as sent if either email or notification was successful
            if ($emailSent || $notificationSent) {
                $reminderModel->markAsSent($reminderId);
                error_log("Reminder {$reminderId} marked as sent (email: " . ($emailSent ? "true" : "false") . ", notification: " . ($notificationSent ? "true" : "false") . ")");
                $successfulCount++;
            } else {
                error_log("Failed to send reminder for user {$userId}, item {$itemTitle} (ID: {$reminderId})");
                $failedCount++;
            }
        } catch (Exception $e) {
            error_log("Error processing reminder ID {$reminderId}: " . $e->getMessage());
            $failedCount++;
        }

        $processedCount++;
    }

    error_log("Cron job completed. Processed {$processedCount} reminders. Successful: {$successfulCount}, Failed: {$failedCount}");
    echo "Cron job completed. Processed {$processedCount} reminders. Successful: {$successfulCount}, Failed: {$failedCount}\n";
} catch (Exception $e) {
    error_log("Cron job failed with error: " . $e->getMessage());
    echo "Cron job failed with error: " . $e->getMessage() . "\n";
}

?>