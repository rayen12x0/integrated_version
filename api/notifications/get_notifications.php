<?php
// API endpoint to get user notifications
// This file connects the frontend to the notification controller

require_once "../../controllers/NotificationController.php";


$controller = new NotificationController();
$controller->getByUser();
