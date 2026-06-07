<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['sess_driver'] = 'redis';
$config['sess_save_path'] = [
    'host' => getenv('REDIS_HOST') ?: 'redis',
    'password' => getenv('REDIS_PASSWORD') ?: NULL,
    'port' => 6379,
    'database' => 1
];
$config['sess_cookie_name'] = 'mina_session';
$config['sess_expiration'] = 7200;
$config['sess_save_path'] = 'tcp://' . (getenv('REDIS_HOST') ?: 'redis') . ':6379?auth=' . (getenv('REDIS_PASSWORD') ?: '');
$config['sess_match_ip'] = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = FALSE;
$config['cookie_prefix'] = 'mina_';
$config['cookie_domain'] = '';
$config['cookie_path'] = '/';
$config['cookie_secure'] = TRUE;
$config['cookie_httponly'] = TRUE;
$config['cookie_samesite'] = 'Lax';
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'csrf_token';
$config['csrf_cookie_name'] = 'csrf_cookie';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = TRUE;
$config['csrf_exclude_uris'] = ['api/v1/tenants/register'];
