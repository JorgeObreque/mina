<?php
define('ENVIRONMENT', isset($_SERVER['APP_ENV']) ? $_SERVER['APP_ENV'] : 'production');

switch (ENVIRONMENT) {
    case 'development':
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        break;
    case 'testing':
    case 'production':
        ini_set('display_errors', 0);
        if (version_compare(PHP_VERSION, '5.3', '>=')) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        break;
    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'Unknown environment';
        exit(1);
}

$system_path = 'system';
$application_folder = 'application';

define('BASEPATH', $system_path . DIRECTORY_SEPARATOR);
define('APPPATH', $application_folder . DIRECTORY_SEPARATOR);
define('SYSDIR', basename($system_path));
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('SUBDIR', isset($_SERVER['SUBDIR']) ? $_SERVER['SUBDIR'] : '/');
define('STDERR', fopen('php://stderr', 'w'));
define('STDOUT', fopen('php://stdout', 'w'));

require_once BASEPATH . 'core/CodeIgniter.php';
