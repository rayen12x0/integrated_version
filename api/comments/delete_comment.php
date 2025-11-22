<?php
// API endpoint to delete a user's comment
// This file connects the frontend to the comment controller

require_once "../../controllers/CommentController.php";

// Create controller instance and call delete by ID method
$controller = new CommentController();
$controller->deleteById();
