<?php
define('INDEX', true);
$GLOBALS['SYS']['included'][] = __FILE__;
$GLOBALS['SYS']['runtime'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

$_FINALIZERS = array();

require('config-global.php');
require('config-server.php');
require('config-custom.php');

require('libs/inc.common.php');
require('libs/inc.registry.php');
require('libs/inc.request.php');
require('libs/inc.response.php');

core_use_handler(sys_get('route.path'));

function register_finalizer(callable $func_name)
{
    global $_FINALIZERS;
    array_push($_FINALIZERS, $func_name);
}

foreach ($_FINALIZERS as $finalizer)
{
    if (is_callable($finalizer)) $finalizer();
}

#
# Run helpers (debug)

if (IS_VIP) require('libs/inc.helpers.php');

