<?php
// API endpoint to update an existing resource
// This file connects the frontend to the controller

require_once "../../controllers/resourceController.php";
require_once "../../utils/AuthHelper.php";


$controller = new ResourceController();
$controller->update();
