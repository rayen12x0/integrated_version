<?php
// api/get_participants.php
// API endpoint to get participants for an action

require_once "../controllers/ActionParticipantController.php";


$controller = new ActionParticipantController();
$controller->getParticipants();