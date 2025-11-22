<?php
// API endpoint to update an existing comment
// This file connects the frontend to the comment controller

require_once "../controllers/CommentController.php";


$controller = new CommentController();
$controller->update();