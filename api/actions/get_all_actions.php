<?php
// API endpoint to get all actions (admin view)
// This file connects the frontend to the action controller

require_once "../../controllers/actionController.php";


$controller = new ActionController();
$controller->getAll();
