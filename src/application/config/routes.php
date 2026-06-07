<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

$route['api/v1/tenants/register'] = 'api/v1/tenants/register';
$route['api/v1/tenants'] = 'api/v1/tenants/index';
$route['api/v1/tenants/settings'] = 'api/v1/tenants/settings';
$route['api/v1/tenants/plan'] = 'api/v1/tenants/plan';
$route['api/v1/appointments'] = 'api/v1/appointments/index';
$route['api/v1/services'] = 'api/v1/services/index';
$route['api/v1/providers'] = 'api/v1/providers/index';
$route['api/v1/customers'] = 'api/v1/customers/index';
