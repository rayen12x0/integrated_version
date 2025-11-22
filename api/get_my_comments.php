<?php
// API endpoint to get user's comments
// This file connects the frontend to the comment controller

require_once "../controllers/CommentController.php";

// Create controller instance and call get by user method
$controller = new CommentController();
$controller->getByUser();