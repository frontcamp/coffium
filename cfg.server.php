<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Environment-dependent configuration */


/**
 * SERVER-DEPENDENT CONFIGURATION
 * ------------------------------
 * (environment-specific DB and other settings)
 */

# TODO: override DB_* per environment
# or add more environmental constants

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
    define('DB_USER', 'db_dev_user');
    define('DB_PASS', 'db_dev_password');
    define('DB_NAME', 'db_dev_name');
}
elseif (IS_PROD)  # production server
{
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'db_prod_user');
    define('DB_PASS', 'db_prod_password');
    define('DB_NAME', 'db_prod_name');
}

if (IS_CRON)  # CRON task
{
    // TODO: cron-specific overrides (DB, logging, limits, etc.)
}

