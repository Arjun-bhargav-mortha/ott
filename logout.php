<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

Auth::init();

if (Auth::isLoggedIn()) {
    logActivity('logout', Auth::getUserId());
    Auth::logout();
} else {
    header('Location: /login.php');
    exit;
}