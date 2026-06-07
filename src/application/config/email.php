<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['email_protocol'] = 'smtp';
$config['email_mailpath'] = '/usr/sbin/sendmail';
$config['email_smtp_host'] = getenv('SMTP_HOST') ?: 'localhost';
$config['email_smtp_user'] = getenv('SMTP_USER') ?: '';
$config['email_smtp_pass'] = getenv('SMTP_PASS') ?: '';
$config['email_smtp_port'] = getenv('SMTP_PORT') ?: 1025;
$config['email_smtp_timeout'] = 5;
$config['email_smtp_keepalive'] = FALSE;
$config['email_smtp_crypto'] = 'tls';
$config['email_wordwrap'] = TRUE;
$config['email_mailtype'] = 'html';
$config['email_charset'] = 'UTF-8';
$config['email_validate'] = TRUE;
$config['email_priority'] = 3;
$config['email_newline'] = "\r\n";
$config['email_crlf'] = "\r\n";
$config['email_badge_batch_size'] = 500;
