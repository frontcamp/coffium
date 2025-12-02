<?php
define('INDEX', true);
$GLOBALS['SYS']['included'][] = __FILE__;
$GLOBALS['SYS']['runtime'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
/* Coffium core entry point */

## Check PHP version

if (version_compare(PHP_VERSION, '8.0.0', '<'))
{
    die('Required PHP version 8.0.0 or higher! Current: '.PHP_VERSION);
}

## Define core ID

define('FRAMEWORK', 'Coffium');
define('CORE_NAME', 'White Tiger');
define('CORE_VERSION', '0.5');

## Define core response statuses

define('CORE_STATUS_OK',         0);
define('CORE_STATUS_TERMINATED', 1);

## Define finalizer priorities

define('CORE_PRIO_LOW',     -5);  # data processing, pre-finalization
define('CORE_PRIO_NORMAL',   0);  # routine finalizations, by default
define('CORE_PRIO_HIGH',    10);  # cache, stats,.. on finalized data
define('CORE_PRIO_HIGHEST', 50);  # shutdown of critical resources (DB, FS)

$_FINALIZERS = array();
function core_register_finalizer(callable $func_name, int $priority=CORE_PRIO_NORMAL)
{
    global $_FINALIZERS;
    $_FINALIZERS[$priority][] = $func_name;
}

## Initialization

require('cfg.global.php');
require('cfg.server.php');
require('cfg.custom.php');

## Error handling

define('_ERR_NAMES', array(
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
));

function error_handler($err_no, $err_str, $err_file, $err_line)
{
    if (!(error_reporting() & $err_no)) return;
    $err_name = _ERR_NAMES[$err_no] ?? 'Unknown error';
    $err_text = "$err_name: $err_str in $err_file on line $err_line";
    $err_html = "<b>$err_name</b>: $err_str in <b>$err_file</b> on line <b>$err_line</b><br>\n";
    if (ini_get('log_errors')) { error_log($err_text); }
    if (ini_get('display_errors')) { print_r($err_html); }
    if (E_USER_ERROR == $err_no) die();
    return true;
}

set_error_handler('error_handler');

## Debug features

if (IS_LOCAL || IS_DEV || IS_VIP) require('libs/inc.dump.php');

## Include core libraries

require('libs/inc.common.php');
require('libs/inc.registry.php');
require('libs/inc.request.php');
require('libs/inc.response.php');

## Request processing

try
{
    # run router
    core_use_handler(sys_get('route.path'));

    # set response status
    sys_opt('response', 'status', CORE_STATUS_OK);
}
catch (CoreTerminateRoute $e)
{
    while (ob_get_level() > 0) ob_end_clean();  # clean output buffer

    $message = $e->getMessage();

    # set response status
    sys_opt('response', 'status', CORE_STATUS_TERMINATED);
    sys_opt('response', 'message', $message);

    if ($e->has_redirect())
    {
        $location = $e->redirect_url;
        $status   = $e->redirect_status ?? 302;
        $by       = $e->redirect_by ?? CORE_NAME;

        # register redirect finalizer
        core_register_finalizer(
            function () use ($location, $status, $by) {
                redirect($location, $status, $by);
            },
            PHP_INT_MAX
        );

        sys_opt('response', 'redir_url',  $location);
        sys_opt('response', 'redir_code', $status);
        sys_opt('response', 'redir_by',   $by);
    }
}

## Run finalizers (allow registration during execution)

if (IS_LOCAL || IS_DEV || IS_VIP) {
    $_FINALIZERS_SNAPSHOT = $_FINALIZERS;  # for debug purposes
}
while (!empty($_FINALIZERS))
{
    ksort($_FINALIZERS);
    $prio_group = array_key_first($_FINALIZERS);
    while (!empty($_FINALIZERS[$prio_group]))
    {
        $finalizer = array_shift($_FINALIZERS[$prio_group]);
        $finalizer();
    }
    unset($_FINALIZERS[$prio_group]);
}

## Run debug helpers

if (IS_LOCAL || IS_DEV || IS_VIP) require('libs/inc.helpers.php');

