<?php
// API endpoint to update an existing action
// This file connects the frontend to the controller

require_once "../../controllers/actionController.php";
require_once "../../utils/AuthHelper.php";


$controller = new ActionController();
$controller->update();
