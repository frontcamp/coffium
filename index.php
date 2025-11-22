<?php
define('INDEX', true);
$GLOBALS['SYS']['included'][] = __FILE__;
$GLOBALS['SYS']['runtime'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

# check PHP version
if (version_compare(PHP_VERSION, '8.3.0', '<'))
{
    die('Required PHP version 8.3.0 or higher! Current: '.PHP_VERSION);
}

# core ID
define('FRAMEWORK', 'Coffium');
define('CORE_NAME', 'White Tiger');
define('CORE_VERSION', '0.5');

# finalizer priorities
define('FIN_PRIO_LOW',     -5);  # data processing, pre-finalization
define('FIN_PRIO_NORMAL',   0);  # routine finalizations, by default
define('FIN_PRIO_HIGH',    10);  # cache, stats,.. on finalized data
define('FIN_PRIO_HIGHEST', 50);  # shutdown of critical resources (DB, FS)

$_FINALIZERS = array();
function register_finalizer(callable $func_name, int $priority=FIN_PRIO_NORMAL)
{
    global $_FINALIZERS;
    $_FINALIZERS[$priority][] = $func_name;
}

# initialization
require('config-global.php');
require('config-server.php');
require('config-custom.php');

# debug & dump
if (IS_VIP) require('libs/inc.dump.php');

# include core libraries
require('libs/inc.common.php');
require('libs/inc.registry.php');
require('libs/inc.request.php');
require('libs/inc.response.php');

# run autorun
$glob_init = COMS_ROOT.'/__init__.php';
if (is_file($glob_init))
{
    require($glob_init);
    sys_opt_push('ini.loaded', $glob_init);
}

# run router
core_use_handler(sys_get('route.path'));

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

