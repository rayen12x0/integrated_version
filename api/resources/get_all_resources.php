<?php
// API endpoint to get all resources (admin view)
// This file connects the frontend to the resource controller

require_once "../../controllers/resourceController.php";


$controller = new ResourceController();
$controller->getAll();
