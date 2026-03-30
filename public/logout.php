<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Tijd\Auth\AuthService;

$authService = new AuthService();
$authService->logout();

$_SESSION['flash_success'] = 'Je bent uitgelogd.';
header('Location: login.php');
exit;