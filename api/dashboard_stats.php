<?php
// API endpoint to get dashboard statistics
// Mock endpoint for testing

header("Content-Type: application/json");

// For testing purposes, return mock data
echo json_encode([
    "success" => true,
    "myActionsCount" => rand(5, 25),
    "myResourcesCount" => rand(3, 15),
    "participatedCount" => rand(2, 10),
    "commentsCount" => rand(1, 20)
]);