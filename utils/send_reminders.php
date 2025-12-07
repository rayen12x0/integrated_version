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
        $itemId = $reminder['item_id'];

        error_log("Processing reminder ID: {$reminderId} for user {$userId}, item {$itemTitle}");

        try {
            $emailSent = false;
            $notificationSent = false;

            // If it's an action reminder, send to all participants
            if ($itemType === 'action') {
                // Get all participants for this action
                $sql = "SELECT u.id, u.email, u.name
                        FROM action_participants ap
                        JOIN users u ON ap.user_id = u.id
                        WHERE ap.action_id = :item_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':item_id', $itemId, PDO::PARAM_INT);
                $stmt->execute();
                $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Process reminder for each participant
                foreach ($participants as $participant) {
                    $participantEmail = $participant['email'];
                    $participantName = $participant['name'];
                    $participantId = $participant['id'];

                    // Send email if requested
                    if ($reminderType === 'email' || $reminderType === 'both') {
                        error_log("Sending email reminder to {$participantEmail} for action {$itemTitle}");
                        $emailResult = EmailService::sendReminderEmail($participantEmail, $participantName, $itemTitle, $itemType, $itemDate, $itemLocation);
                        if ($emailResult) {
                            $emailSent = true; // Mark as sent if at least one email was sent
                        }
                        error_log("Email result for action reminder to user {$participantId}: " . ($emailResult ? "SUCCESS" : "FAILED"));
                    }

                    // Create in-app notification if requested
                    if ($reminderType === 'in_app' || $reminderType === 'both') {
                        error_log("Creating in-app notification for user {$participantId} for action {$itemTitle}");
                        $notificationMessage = "Reminder: Upcoming {$itemType} - {$itemTitle}";
                        $notificationData = [
                            'user_id' => $participantId,
                            'type' => 'reminder',
                            'message' => $notificationMessage,
                            'related_id' => $itemId
                        ];
                        $result = $notificationModel->create($notificationData);
                        if ($result) {
                            $notificationSent = true; // Mark as sent if at least one notification was created
                        }
                        error_log("Notification result for action reminder to user {$participantId}: " . ($result ? "SUCCESS" : "FAILED"));
                    }
                }
            } else {
                // For non-action items (like resources), send to the original reminder creator
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
                        'related_id' => $itemId
                    ];
                    $result = $notificationModel->create($notificationData);
                    $notificationSent = $result;
                    error_log("Notification result for reminder {$reminderId}: " . ($result ? "SUCCESS" : "FAILED"));
                }
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