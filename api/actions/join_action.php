<?php
// api/join_action.php
// API endpoint to join or leave an action

session_start();
require_once "../../controllers/ActionParticipantController.php";


$controller = new ActionParticipantController();
$controller->joinAction();
