<?php
session_start();
require_once '../../controllers/ReminderController.php';

$controller = new ReminderController();
$controller->getByUser();
?>