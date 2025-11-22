<?php
// API endpoint to mark notification as read

require_once "../controllers/NotificationController.php";


$controller = new NotificationController();
$controller->markAsRead();