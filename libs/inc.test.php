<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Testing API */


if (!defined('P_NULL'))
{
    define('P_NULL', 'P$#_D0');  # Pseudo NULL
    function is_pnull($value) { return $value === P_NULL; }
}

function _vhash($v) { return md5(serialize($v)); }

function _assert($value, ...$args)
{
    # reaching backtrace data
    $backtrace = debug_backtrace();
    if (!is_array($backtrace) or !isset($backtrace[1])) {
        trigger_error('Error reaching backtrace data!', E_USER_ERROR);
        return NULL;
    }
    $last = $backtrace[1];
    if (!isset($last['function']) or !isset($last['file'])  or !isset($last['line'])) {
        trigger_error('Invalid backtrace data format!', E_USER_ERROR);
        return NULL;
    }

    # extra info
    $vdmp = '';
    foreach ($args as $i => $gval) {
        $vdmp .= var_export($gval, true);
        if ($i < count($args) - 1) $vdmp .= ', ';
    }

    # assertion
    if ($value === true) {
        if (isset($_REQUEST['verbose'])) {
            print("<b>Assertion passed!</b> "
                 ."Function: <b>{$last['function']}($vdmp)</b>"
                 ." called from: <b>{$last['file']}</b>"
                 ." on line: <b>{$last['line']}</b><br>\n");
        }
        return true;
    }

    # output
    error_log("Assertion failed! {$last['function']}($vdmp)"
             ." in {$last['file']}:{$last['line']}");
    print("<b>Assertion failed!</b> "
         ."Function: <b>{$last['function']}($vdmp)</b>"
         ." called from: <b>{$last['file']}</b>"
         ." on line: <b>{$last['line']}</b><br>\n");
    return false;
}


function core_assert($v) { return _assert($v == true, $v); }
function core_assert_array($a) { return _assert(is_array($a), '$a'); }
function core_assert_key_exists($k,$a) { return _assert(array_key_exists($k,$a), $k, '$a'); }
function core_assert_bool($v) { return _assert(is_bool($v), $v); }
function core_assert_defined($c) { return _assert(defined($c), $c); }
function core_assert_defined_and_equal($c, $v) { return _assert(defined($c), $c) and _assert(constant($c) == $v, $c, $v); }
function core_assert_defined_and_same($c, $v) { return _assert(defined($c), $c) and _assert(constant($c) === $v, $c, $v); }
function core_assert_dir($p) { return _assert(is_dir($p), $p); }
function core_assert_empty($v) { return _assert(empty($v), $v); }
function core_assert_equal($a, $b) { return _assert($a == $b, $a, $b); }
function core_assert_false($v) { return _assert($v === false, $v); }
function core_assert_file($p) { return _assert(is_file($p), $p); }
function core_assert_float($v) { return _assert(is_float($v), $v); }
function core_assert_gt($a, $b) { return _assert($a > $b, $a, $b); }
function core_assert_gte($a, $b) { return _assert($a >= $b, $a, $b); }
function core_assert_int($v) { return _assert(is_int($v), $v); }
function core_assert_lt($a, $b) { return _assert($a < $b, $a, $b); }
function core_assert_lte($a, $b) { return _assert($a <= $b, $a, $b); }
function core_assert_null($v) { return _assert(is_null($v), $v); }
function core_assert_numeric($v) { return _assert(is_numeric($v), $v); }
function core_assert_object($v) { return _assert(is_object($v), $v); }
function core_assert_pnull($v) { return _assert(is_pnull($v), $v); }
function core_assert_resource($r) { return _assert(is_resource($r), strval($r)); }
function core_assert_same($a, $b) { return _assert($a === $b, $a, $b); }
function core_assert_same_arrays($a1, $a2) { return _assert(_vhash($a1) === _vhash($a2), '$a1, $a2'); }
function core_assert_string($v) { return _assert(is_string($v), $v); }
function core_assert_true($v) { return _assert($v, $v); }
function core_assert_zero($v) { return _assert($v === 0, $v); }


# if $path is a script file - run all *_test functions in this file
# if $path is a folder - run all test scripts in this folder and its subfolders
function launch_tests($path)
{
    $path = rtrim($path, '\\/');
    if (is_dir($path))  // run all tests in the folder
    {
        $file_list = scandir($path, SCANDIR_SORT_NONE);  # order doesn't matter
        foreach($file_list as $file_name)
        {
            if ($file_name == '.' or $file_name == '..') continue;
            launch_tests($path.'/'.$file_name);  # recursion!
        }
        return;
    }

    if (!is_file($path))  // test file must be existing and readable
    {
        trigger_error('File does not exist and/or not readable:'.$path, E_USER_ERROR);
        die();
    }

    $funcs = get_defined_functions()["user"];
    require($path);
    $tests = array_values(array_diff(get_defined_functions()["user"], $funcs));

    foreach($tests as $test_func)
    {
        if (str_starts_with($test_func, '_')) continue;  # skip privates
        if (str_ends_with($test_func, '_test'))
        {
            if (isset($_REQUEST['verbose'])) { print "<hr>Test: file <b>$path</b>, function <b>$test_func()</b><br>\n"; }
            $test_func();  # run test
        }
    }
}

