<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Log out</title></head><body><p role="alert">Use the Log Out button in your portal to end your session.</p><a href="index.php">Return to the portal</a></body></html>';
    exit;
}
App::verifyPost();
Auth::logout();
Flash::set('success', 'You have been logged out successfully.');
header('Location: login.php', true, 303);
exit;

