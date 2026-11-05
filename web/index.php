<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::start();

if (Auth::check()) {
    Auth::redirectIfLoggedIn();
} else {
    header('Location: login.php');
    exit;
}

