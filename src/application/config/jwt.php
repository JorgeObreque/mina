<?php
/**
 * Tenant configuration (loads from environment).
 */
defined('BASEPATH') or exit('No direct script access allowed');

$config['jwt'] = [
    'secret' => getenv('JWT_SECRET') ?: 'change-me-in-production-please-32chars-min',
    'ttl'    => (int) (getenv('JWT_TTL') ?: 3600),
    'algo'   => 'HS256',
];

$config['tenant'] = [
    'default_id'   => 1,
    'header_name'  => 'Authorization',
    'cookie_name'  => 'tenant_id',
    'session_key'  => 'tenant_id',
];
