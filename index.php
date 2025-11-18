<?php
define('INDEX', true);
$GLOBALS['SYS']['included'][] = __FILE__;


/*******************************************************************************
 * Profiling API
 *
 * core_timer() usage:
 *   core_timer()            -> create a timer, return its ID
 *   core_timer($id)         -> return delta between saved and current time
 *   core_timer($id, $value) -> set timer value (timestamp or custom)
 *
 * Behaviour:
 *   1) If a timer with $timer_id exists:
 *      a) if $value is given, the timer value is updated
 *      b) otherwise the function returns the time delta
 *   2) If no timer with $timer_id exists, it will be created:
 *      a) if $value is given, the timer is set to $value
 *      b) otherwise the timer is set to the current time
 */
function core_timer($timer_id=NULL, $value=NULL)
{
    static $_CORE_TIMERS = array();

    $current = microtime(true);

    if (is_null($timer_id))  # create new timer
    {
        $timer_id = uniqid('timer_', true);
        $_CORE_TIMERS[$timer_id] = $current;
        return $timer_id;
    }
    else  # $timer_id given
    {
        if (array_key_exists($timer_id, $_CORE_TIMERS))  # ..if timer exists
        {
            if (is_null($value)) {
                return number_format($current - $_CORE_TIMERS[$timer_id], 4);  # return delta
            } else {
                $_CORE_TIMERS[$timer_id] = $value;  # set
            }
        }
        else  # timer does not exist yet, create it
        {
            if (is_null($value)) {
                $_CORE_TIMERS[$timer_id] = $current;  # now
            } else {
                $_CORE_TIMERS[$timer_id] = $value;    # set
            }
        }
    }
}

core_timer('PROC_RUN_TIMER', $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
core_timer('PAGE_GEN_TIMER');


/*******************************************************************************
 * Initialization
 */

$_FINALIZERS = array();

#
# configuration

require('config-global.php');
require('config-server.php');
require('config-custom.php');

#
# debug

if (IS_VIP) require('libs/inc.dump.php');

#
# core libs

require('libs/inc.common.php');
require('libs/inc.registry.php');
require('libs/inc.request.php');
require('libs/inc.response.php');


/*******************************************************************************
 * Emergency Triggers
 */

# Lock/unlock API

function core_lock() { @unlink(ET_FLAG_FILE); }
function core_locked() { return !is_file(ET_FLAG_FILE); }
function core_unlock() { file_put_contents(ET_FLAG_FILE, ''); }

#
# Handle locked core (applies only to non VIP users!)

if (core_locked() && !IS_VIP) die(ET_HEADER.ET_SYSMNT.ET_FOOTER);

#
# Maintenance schedule (PROD only!)

if (defined('ET_SYSMNT_FROM_HR') && defined('ET_SYSMNT_TO_HR') && IS_PROD)
{
    $ET_FROM = new DateTime();
    $ET_FROM->setTime(ET_SYSMNT_FROM_HR, 0);
    $ET_TO = new DateTime();
    $ET_TO->setTime(ET_SYSMNT_TO_HR, 0);
    $ET_NOW = new DateTime();
    if ($ET_FROM <= $ET_NOW && $ET_NOW <= $ET_TO && !IS_VIP) {
        die(ET_HEADER.ET_SYSMNT.ET_FOOTER);
    }
}

#
# Check CORE integrity & readiness

$is_ready = is_file(ET_FLAG_FILE);
$is_coms = is_dir(COMS_ROOT);
if ((!$is_ready || !$is_coms) && !(IS_VIP && $is_coms))
{
    die(ET_HEADER.ET_SYSMNT.ET_FOOTER);
}


/*******************************************************************************
 * Find handler, generate response
 */

core_use_handler(sys_get('route.path'));


/*******************************************************************************
 * Finalization
 */

function core_register_finalizer(callable $func_name)
{
    global $_FINALIZERS;
    array_push($_FINALIZERS, $func_name);
}

#
# Run finalization functions

foreach ($_FINALIZERS as $finalizer)
{
    if (is_callable($finalizer)) $finalizer();
}

#
# Run helpers (debug)

if (IS_VIP) require('libs/inc.helpers.php');

#
# Output timings

$prt = core_timer('PROC_RUN_TIMER');
$pgt = core_timer('PAGE_GEN_TIMER');
$dlt = number_format($prt - $pgt, 4);

if (IS_VIP && isset($_REQUEST['verbose']))
{
    print("PHP process run time: <b>$prt sec</b>; ");
    print("page generation time: <b>$pgt sec</b>; ");
    print("PHP process overhead: <b>$dlt sec</b>.\n");
}

if (IS_VIP && isset($_REQUEST['profiling']))
{
    dump_js_log('PHP process run time: '.$prt.' sec.');
    dump_js_log('Page generation time: '.$pgt.' sec.');
    dump_js_log('PHP process overhead: '.$dlt.' sec.');
}

