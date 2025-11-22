<?php
// API endpoint to mark all notifications as read for a user
// This file connects the frontend to the notification controller

require_once "../../controllers/NotificationController.php";


$controller = new NotificationController();
$controller->markAllAsRead();
