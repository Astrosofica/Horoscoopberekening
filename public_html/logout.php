<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Astro\Auth\AuthService;

$authService = new AuthService();

$_SESSION['flash_success'] = 'Je bent uitgelogd.';
$authService->logout();

header('Location: index.php');
exit;