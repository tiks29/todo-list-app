<?php
session_start();
require_once 'vendor/autoload.php';

use Config\DependencyContainer;

$container = new DependencyContainer();

$db = $container->get('database');
$authService = $container->get('authService');

// Check if user is logged in and has admin role
if (!$authService->isLoggedIn() || !$authService->hasRole('admin')) {
    header('Location: index.php');
    exit;
}
$authService->logout();

header('Location: index.php');
exit;
?>

