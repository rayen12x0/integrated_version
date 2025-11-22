<?php
// API endpoint to get approved actions (for public display)
// This file connects the frontend to the controller

require_once "../controllers/actionController.php";


$controller = new ActionController();
$controller->getApproved();