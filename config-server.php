<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;

/*
 * Server-depending configuration
 */

if (IS_LOCAL)  # local server
{
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'db_local_user');
    define('DB_PASS', 'db_local_password');
    define('DB_NAME', 'db_local_name');
}
elseif (IS_DEV)  # development server
{
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'db_local_user');
    define('DB_PASS', 'db_local_password');
    define('DB_NAME', 'db_local_name');
}
elseif (IS_PROD)  # production server
{
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'db_local_user');
    define('DB_PASS', 'db_local_password');
    define('DB_NAME', 'db_local_name');
}

if (IS_CRON)  # CRON task (can be run on dev or prod server)
{

}

