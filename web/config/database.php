<?php
return [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'dbname'   => getenv('DB_NAME') ?: 'u312211237_dcss_db',
    'username' => getenv('DB_USER') ?: 'u312211237_dbuser',
    'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Paulgabrielcamacho21',
    'charset'  => 'utf8mb4',
    'port'     => (int)(getenv('DB_PORT') ?: 3306),
];

