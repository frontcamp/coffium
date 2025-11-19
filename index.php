<?php
define('INDEX', true);
$GLOBALS['SYS']['included'][] = __FILE__;
$GLOBALS['SYS']['runtime'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

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

# include core libraries
require('libs/inc.common.php');
require('libs/inc.registry.php');
require('libs/inc.request.php');
require('libs/inc.response.php');

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

