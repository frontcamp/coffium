<?php
define('INDEX', true);
$GLOBALS['SYS']['included'][] = __FILE__;
$GLOBALS['SYS']['runtime'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

$_FINALIZERS = array();
function register_finalizer(callable $func_name, int $order=0)
{
    global $_FINALIZERS;
    $_FINALIZERS[$order][] = $func_name;
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

# run finalizers (registration during execution is allowed)
while (!empty($_FINALIZERS))
{
    ksort($_FINALIZERS);
    $group = array_key_first($_FINALIZERS);
    while (!empty($_FINALIZERS[$group]))
    {
        $finalizer = array_shift($_FINALIZERS[$group]);
        $finalizer();
    }
    unset($_FINALIZERS[$group]);
}

if (IS_VIP) require('libs/inc.helpers.php');

