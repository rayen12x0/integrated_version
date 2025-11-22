<?php
require_once '../controllers/resourceController.php';
require_once "../utils/AuthHelper.php";

$controller = new ResourceController();
$controller->delete();
?>