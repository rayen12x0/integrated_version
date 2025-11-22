<?php
// API endpoint to get comments for an action or resource
// This file connects the frontend to the comment controller

require_once "../controllers/CommentController.php";

// Get input parameters
$actionId = $_GET['action_id'] ?? null;
$resourceId = $_GET['resource_id'] ?? null;


$controller = new CommentController();

if ($actionId || $resourceId) {
    // Get comments by entity (action or resource)
    $controller->getByEntity();
} else {
   
    $controller->getAll();
}