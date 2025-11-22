<?php
// API endpoint to get a specific action by ID for editing
// This file connects the frontend to the controller

require_once "../../controllers/actionController.php";


$id = $_GET['id'] ?? null;

if (!$id) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Action ID is required"
    ]);
    exit;
}


$controller = new ActionController();
$controller->getById($id);
