<?php
// API endpoint to get approved resources (for public display)
// This file connects the frontend to the controller

require_once '../controllers/resourceController.php';

$controller = new ResourceController();
$controller->getApproved();