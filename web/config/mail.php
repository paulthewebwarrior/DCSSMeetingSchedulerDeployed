<?php
// Local overrides are kept outside source control. Environment variables also work.
$config = [
    'username' => getenv('GMAIL_SMTP_USERNAME') ?: 'nytriee@gmail.com',
    'app_password' => getenv('GMAIL_SMTP_APP_PASSWORD') ?: 'zpcbqytbezazspgg',
];
$localFile = __DIR__ . '/mail.local.php';
if (is_file($localFile)) {
    $config = array_replace($config, require $localFile);
}
return $config;
