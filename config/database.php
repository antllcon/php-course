<?php
// config/database.php
return [
    'host' => 'localhost',
    'name' => 'php_course',
    'user' => 'root',
    'password' => '1234',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
];