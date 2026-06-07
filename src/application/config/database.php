<?php
$active_group = 'default';
$query_builder = TRUE;

$db['default'] = [
    'dsn' => '',
    'hostname' => getenv('DB_HOST') ?: 'mysql',
    'username' => getenv('DB_USER') ?: 'agenda_user',
    'password' => getenv('DB_PASS') ?: '',
    'database' => getenv('DB_NAME') ?: 'agenda_saas',
    'dbdriver' => 'mysqli',
    'dbprefix' => 'ea_',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => TRUE,
    'failover' => [],
    'save_queries' => (ENVIRONMENT !== 'production')
];
