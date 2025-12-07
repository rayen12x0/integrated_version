<?php
// utils/email_helper.php
// Dummy email helper for the application

function sendEmail($to, $subject, $message) {
    // This is a simulation since actual email sending requires proper configuration
    // In a production environment, this would use PHPMailer or SwiftMailer
    
    try {
        // Log email attempt
        error_log("Email attempt to: $to, Subject: $subject, Message: $message");
        
        // Simulate email sending success (in real implementation, actual sending would happen here)
        return true;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}