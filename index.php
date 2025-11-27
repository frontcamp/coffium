<?php
define('INDEX', true);
$GLOBALS['SYS']['included'][] = __FILE__;
$GLOBALS['SYS']['runtime'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

# check PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<'))
{
    die('Required PHP version 8.0.0 or higher! Current: '.PHP_VERSION);
}

# core ID
define('FRAMEWORK', 'Coffium');
define('CORE_NAME', 'White Tiger');
define('CORE_VERSION', '0.5');

# core response statuses
define('CORE_STATUS_OK',         0);
define('CORE_STATUS_TERMINATED', 1);

# finalizer priorities
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

# initialization
require('cfg.global.php');
require('cfg.server.php');
require('cfg.custom.php');

# debug & dump
if (IS_VIP) require('libs/inc.dump.php');

# include core libraries
require('libs/inc.common.php');
require('libs/inc.registry.php');
require('libs/inc.request.php');
require('libs/inc.response.php');

try
{
    # run autorun
    $glob_init = COMS_ROOT.'/__init__.php';
    if (is_file($glob_init))
    {
        require($glob_init);
        sys_opt_push('ini.loaded', $glob_init);
    }

    # run router
    core_use_handler(sys_get('route.path'));

    # set response status
    sys_opt('response', 'status', CORE_STATUS_OK);
}
catch (CoreTerminateRoute $e)
{
    $message = $e->getMessage();

    # set response status
    sys_opt('response', 'status', CORE_STATUS_TERMINATED);
    sys_opt('response', 'message', $message);

    if ($e->hasRedirect())
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

# run finalizers (allow registration during execution)
if (IS_VIP) $_FINALIZERS_SNAPSHOT = $_FINALIZERS;  # debug
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

# run debug helpers
if (IS_VIP) require('libs/inc.helpers.php');

