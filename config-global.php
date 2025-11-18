<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;

if (version_compare(PHP_VERSION, '8.0.0', '<'))
{
    die('Required PHP version 8.0.0 or higher! Current: '.PHP_VERSION);
}


/**
 * SYSTEM INFO
 */

#
# Core

define('FRAMEWORK', 'Coffium');
define('CORE_NAME', 'White Tiger');
define('CORE_VERSION', '0.4');

#
# Project

define('ML_URL_SUPPORT', false);  # skip 1st request part, like: domain/en-us/..
define('ML_DIR_SUPPORT', true);   # auto load /d1/d2/_lang/<name>.php files


/**
 * EMERGENCY TRIGGERS
 */

define('ET_FLAG_FILE', '-ready');

# Maintenance schedule (comment to disable)

define('ET_SYSMNT_FROM_HR', 2);   # 24hr time format w/o leading zeros
define('ET_SYSMNT_TO_HR', 3);     # 24hr time format w/o leading zeros

# Trigger messages

define('ET_HEADER', '<!DOCTYPE html><html lang="en"><body><center style="color:#bbb;font-size:10vw;">');
define('ET_FOOTER', '</center></body></html>');

define('ET_ATTACK', 'We are under attack! Please visit us later&hellip;');
define('ET_SYSMNT', 'System maintenance. Will be back shortly&hellip;');


/**
 * SERVER TYPE
 */

function norm_path($path)
{
    return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
}

# IMPORTANT: change to detect local project correctly!
if (!defined('LOCAL_PATH_SIGN')) define('LOCAL_PATH_SIGN', 'server\domains\coffium');

$sign_path_normalized = norm_path(LOCAL_PATH_SIGN);
$self_path_normalized = norm_path(__DIR__);

define('IS_CRON', substr(PHP_SAPI, 0, 3) == 'cli' || !isset($_SERVER['HTTP_HOST']));
define('IS_LOCAL', (false !== stripos($self_path_normalized, $sign_path_normalized)));
define('IS_DEV', substr(($_SERVER['HTTP_HOST'] ?? ''), 0, 4) == 'dev.');
define('IS_PROD', !(IS_LOCAL || IS_DEV));

$env = IS_LOCAL ? 'Local' : (IS_DEV ? 'Development' : 'Production');
define('SERVER_TYPE', $env.(IS_CRON ? '-Cron' : ''));


/**
 * SYSTEM PATHS
 */

# Time-based hash for static URLs (use to force browser cache reset)
# on DEV server - each second; on PROD server - hourly
define('TIME_HASH', IS_PROD ? date('YmdH') : date('YmdHis'));

# Project root
$docroot = $_SERVER['DOCUMENT_ROOT'] ?? '';
if ($docroot === '') $docroot = __DIR__;
define('PROJ_ROOT', rtrim($docroot, '/\\'));

# Components
define('COMS_PATH', '/coms');
define('COMS_ROOT', PROJ_ROOT.COMS_PATH);

# Data
define('DATA_PATH', '/data');
define('DATA_ROOT', PROJ_ROOT.DATA_PATH);

# Logs
define('LOGS_PATH', '/logs');
define('LOGS_ROOT', PROJ_ROOT.LOGS_PATH);

# Temp
define('TEMP_PATH', '/temp');
define('TEMP_ROOT', PROJ_ROOT.TEMP_PATH);


/**
 * PHP
 */

# Date & Time

date_default_timezone_set('UTC');

# Security

ini_set('allow_url_include', 0);
ini_set('magic_quotes_gpc', 0);  // it is no longer supported, but for sure :)
ini_set('register_globals', 0);

# zlib

ini_set('zlib.output_compression', 1);

# Session

ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', IS_DEV || IS_PROD);

define('PHP_SESSION_LIFETIME', 60 * 60 * 24 * 30);  # 1 month
ini_set('session.auto_start', 0);
ini_set('session.cookie_lifetime', PHP_SESSION_LIFETIME);
ini_set('session.use_cookies', 1);

if (!IS_CRON)
{
    session_name('SMSID');  # session cookie name
    if (session_id() == '') session_start();
    #setcookie(session_name(), session_id(), time() + PHP_SESSION_LIFETIME, '/');
}
else $_SESSION = array();

# Logging

$ERR_NAMES = array(
    E_ERROR => 'Fatal run-time error',                 # 1
    E_WARNING => 'Run-time warning',                   # 2
    E_PARSE => 'Compile-time parse error',             # 4
    E_NOTICE => 'Run-time notice',                     # 8
    E_CORE_ERROR => 'Fatal PHP startup error',         # 16
    E_CORE_WARNING => 'PHP startup warning',           # 32
    E_COMPILE_ERROR => 'Fatal compile-time error',     # 64
    E_COMPILE_WARNING => 'Compile-time warning',       # 128
    E_USER_ERROR => 'User-generated error',            # 256
    E_USER_WARNING => 'User-generated warning',        # 512
    E_USER_NOTICE => 'User-generated notice',          # 1024
    E_STRICT => 'Strict standards suggestion',         # 2048
    E_RECOVERABLE_ERROR => 'Catchable fatal error',    # 4096
    E_DEPRECATED => 'Deprecated warning',              # 8192
    E_USER_DEPRECATED => 'User-generated deprecated notice', # 16384
);

ini_set('display_errors', IS_PROD ? 0 : 1);
ini_set('display_startup_errors', IS_PROD ? 0 : 1);
ini_set('error_log', LOGS_ROOT.'/error.log');
ini_set('error_reporting', E_ALL);
ini_set('log_errors', 1);

set_error_handler('error_handler');
function error_handler($err_no, $err_str, $err_file, $err_line)
{
    global $ERR_NAMES;
    if (!(error_reporting() & $err_no)) return;
    $err_name = $ERR_NAMES[$err_no] ?? 'Unknown error';
    $err_text = "$err_name: $err_str in $err_file on line $err_line";
    $err_html = "<b>$err_name</b>: $err_str in <b>$err_file</b> on line <b>$err_line</b><br>\n";
    if (ini_get('log_errors')) { error_log($err_text); }
    if (ini_get('display_errors')) { print_r($err_html); }
    if (E_USER_ERROR == $err_no) die();
    return true;
}

# Encoding

if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');


/**
 * VIP (exclusive dev/management access)
 * enable via ?projkeeper=on|yes|1
 */

define('VIP_MODE_KEY', 'projkeeper');

if (!isset($_SESSION['core.vip_access']))  // init
{
    $_SESSION['core.vip_access'] = false;
}

if (isset($_REQUEST[VIP_MODE_KEY]))  // override from request
{
    $vip_mode_raw = strtolower($_REQUEST[VIP_MODE_KEY]);
    $_SESSION['core.vip_access'] = in_array($vip_mode_raw, array(1, '1', 'on', 'yes', 'true'), true);
}

define('IS_VIP', $_SESSION['core.vip_access']);

