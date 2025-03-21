<?php
session_start();
require_once '../vendor/autoload.php';

use Config\DependencyContainer;

$container = new DependencyContainer();
$controller = $container->get('userApiController');
$controller->handleRequest();
?>

