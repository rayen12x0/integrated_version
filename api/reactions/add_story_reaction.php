<?php
// api/reactions/add_story_reaction.php
// Turn off error display to prevent HTML from being output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in API responses
ini_set('log_errors', 1); // Log errors to file

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../controllers/StoryReactionController.php';
require_once __DIR__ . '/../../utils/AuthHelper.php';

$controller = new StoryReactionController();
$controller->add();
?>