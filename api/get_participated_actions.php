<?php
// API endpoint to get user's participated actions
// This file connects the frontend to the action participant controller

require_once "../controllers/ActionParticipantController.php";


$controller = new ActionParticipantController();
$controller->getByUser();