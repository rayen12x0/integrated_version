<?php
// API endpoint to delete an existing action
// This file connects the frontend to the controller

require_once "../../controllers/actionController.php";
require_once "../../utils/AuthHelper.php";

// Create controller instance and call delete method
$controller = new ActionController();
$controller->delete();
