<?php
// API endpoint to create a new action
// This file connects the frontend to the controller

require_once "../controllers/actionController.php";
require_once "../utils/AuthHelper.php";

// Create controller instance and call create method
$controller = new ActionController();
$controller->create();