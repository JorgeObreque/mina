<?php
/**
 * Mina SaaS - Configuration File
 *
 * Copy of config-sample.php customized for the Docker dev environment.
 * Production deployments should override via environment / secret manager.
 */
class Config
{
    const BASE_URL = 'http://91.99.168.96';

    const LANGUAGE = 'english';

    const DEBUG_MODE = false;

    const DB_HOST = 'mysql';
    const DB_NAME = 'agenda_saas';
    const DB_USERNAME = 'mina_user';
    const DB_PASSWORD = 'mina_dev_password';

    // Google Calendar sync is optional and can be configured via UI.
    // const GOOGLE_SYNC_FEATURE = false;
    // const GOOGLE_CLIENT_ID = '';
    // const GOOGLE_CLIENT_SECRET = '';
}
