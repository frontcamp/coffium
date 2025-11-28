<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Core and environment configuration */


/**
 * SYSTEM
 * ------
 */

## Multilanguage

define('ML_URL_SUPPORT', false);  # skip 1st request part, like: domain/en-us/..
define('ML_DIR_SUPPORT', true);   # auto load /d1/d2/_lang/<name>.php files


/**
 * SERVER
 * ------
 */

$host = preg_replace('~:\d+$~', '', ($_SERVER['HTTP_HOST'] ?? ''));
$ip = $_SERVER['SERVER_ADDR'] ?? '';
$local_hosts = ['localhost', '127.0.0.1', '::1', '127.0.1.1'];

define('IS_CRON', substr(PHP_SAPI, 0, 3) == 'cli'
              || substr(PHP_SAPI, 0, 6) == 'phpdbg'
              || !isset($_SERVER['HTTP_HOST']));

define('IS_LOCAL', in_array($host, $local_hosts, true)
                || in_array($ip, $local_hosts, true));

define('IS_DEV', str_starts_with($host, 'dev.')
              || str_starts_with($host, 'test.')
              || str_ends_with($host, '.test')
              || str_ends_with($host, '.local'));

define('IS_PROD', !IS_LOCAL && !IS_DEV);

# human-readable environment name
$env_name = match (true) {
    IS_LOCAL => 'Local',      IS_DEV   => 'Development',
    IS_PROD  => 'Production', default  => 'Unknown',
};
define('SERVER_TYPE', $env_name.(IS_CRON ? '-Cron' : ''));


/**
 * PATHS & URLS
 * ------------
 */

## Time-based hash

# hint: use it in URLs (to force browser cache reset)
define('TIME_HASH', IS_PROD ? date('YmdH') : date('YmdHis'));

## Project root

define('PROJ_ROOT', rtrim(__DIR__, '/\\'));

## Components

define('COMS_PATH', '/coms');
define('COMS_ROOT', PROJ_ROOT.COMS_PATH);

## Logs

define('LOGS_PATH', '/logs');
define('LOGS_ROOT', PROJ_ROOT.LOGS_PATH);

## Temp

define('TEMP_PATH', '/temp');
define('TEMP_ROOT', PROJ_ROOT.TEMP_PATH);


/**
 * PHP SETTINGS
 * ------------
 */

## Date & Time

date_default_timezone_set('UTC');

## Security

ini_set('allow_url_include', 0);
ini_set('magic_quotes_gpc', 0);  # no longer supported, but for sure :)
ini_set('register_globals', 0);

## Compression

ini_set('zlib.output_compression', 1);

## Session

ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', IS_DEV || IS_PROD);

define('INI_SESSION_LIFETIME', 60 * 60 * 24 * 30);  # 1 month
ini_set('session.auto_start', 0);
ini_set('session.cookie_lifetime', INI_SESSION_LIFETIME);
ini_set('session.use_cookies', 1);

if (!IS_CRON)
{
    session_name('CFMID');  # session cookie name
    if (session_id() == '') session_start();
    //setcookie(session_name(), session_id(), time() + PHP_SESSION_LIFETIME, '/');
}
else $_SESSION = array();

## Logging

define('INI_LOG_FILE', LOGS_ROOT.'/error.log');

ini_set('display_errors', IS_PROD ? 0 : 1);
ini_set('display_startup_errors', IS_PROD ? 0 : 1);
ini_set('error_log', INI_LOG_FILE);
ini_set('error_reporting', E_ALL);
ini_set('log_errors', 1);

## Encoding

define('INI_ENCODING', 'UTF-8');
define('INI_LANGUAGE', 'uni');

if (!headers_sent()) header('Content-Type: text/html; charset='.INI_ENCODING);
mb_internal_encoding(INI_ENCODING);
mb_http_output(INI_ENCODING);
mb_language(INI_LANGUAGE);
mb_regex_encoding(INI_ENCODING);


/**
 * VIP ACCESS
 * ----------
 */

# exclusive dev/test/admin/edit access
# enable via ?projkeeper=on|yes|1

define('VIP_MODE_KEY', 'projkeeper');

if (!isset($_SESSION['core.vip_access']))  # init
{
    $_SESSION['core.vip_access'] = false;
}

if (isset($_REQUEST[VIP_MODE_KEY]))  # override from request
{
    $vip_mode_raw = strtolower($_REQUEST[VIP_MODE_KEY]);
    $_SESSION['core.vip_access'] = in_array($vip_mode_raw, array(1, '1', 'on', 'yes', 'true'), true);
}

define('IS_VIP', $_SESSION['core.vip_access']);

