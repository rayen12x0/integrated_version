<?php
// api/join_action.php
// API endpoint to join or leave an action

require_once "../controllers/ActionParticipantController.php";


$controller = new ActionParticipantController();
$controller->joinAction();