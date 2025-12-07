<?php
// test_api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
@session_start();

define('DEVELOPMENT_MODE', true);

require_once 'config/config.php';
require_once 'utils/AuthHelper.php';
require_once 'controllers/ReportController.php';

// Mock $_GET for AuthHelper
$_GET['user_id'] = 1;

echo "Testing AuthHelper::getCurrentUser()...\n";
$user = AuthHelper::getCurrentUser();
print_r($user);

echo "\nTesting AuthHelper::isAdmin()...\n";
$isAdmin = AuthHelper::isAdmin();
echo "Is Admin: " . ($isAdmin ? 'Yes' : 'No') . "\n";

echo "\nTesting ReportController::getAll()...\n";
$controller = new ReportController();
// Capture output
ob_start();
$controller->getAll();
$output = ob_get_clean();

echo "Raw Output: " . $output . "\n";
$json = json_decode($output, true);
echo "JSON Decode: \n";
print_r($json);
?>
