<?php
// API endpoint to get approved actions (for public display)
// This file connects the frontend to the controller

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../controllers/actionController.php";

error_log('get_actions.php called');

$controller = new ActionController();
$controller->getApproved();
