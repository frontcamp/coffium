<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Testing API */


if (!defined('P_NULL'))
{
    trigger_error('Require system registry API library!', E_USER_ERROR);
    die();
}

function _vhash($v) { return md5(serialize($v)); }

function _assert($value, ...$args)
{
    # reaching backtrace data
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $frame = $backtrace[1] ?? $backtrace[0];

    $file = $frame['file'] ?? 'unknown';
    $line = $frame['line'] ?? 0;
    $func = $frame['function'] ?? '{global}';

    # extra info
    $vdmp_raw = '';
    $vdmp_prn = '';
    foreach ($args as $i => $gval) {
        $chunk = var_export($gval, true);
        $vdmp_raw .= $chunk;
        $vdmp_prn .= htmlspecialchars($chunk, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
        if ($i < count($args) - 1) {
            $vdmp_raw .= ', ';
            $vdmp_prn .= ', ';
        }
    }

    # assertion
    if ($value === true) {
        if (isset($_REQUEST['verbose'])) {
            print("<b>Assertion passed!</b> "
                 ."Function: <b>{$func}($vdmp_prn)</b>"
                 ." called from: <b>{$file}</b>"
                 ." on line: <b>{$line}</b><br>\n");
        }
        return true;
    }

    # output
    error_log("Assertion failed! {$func}($vdmp_raw)"
             ." in {$file}:{$line}");
    print("<b>Assertion failed!</b> "
         ."Function: <b>{$func}($vdmp_prn)</b>"
         ." called from: <b>{$file}</b>"
         ." on line: <b>{$line}</b><br>\n");
    return false;
}


# Terminology:
#   equal: == (True with type casting: 'a' == True, 12 == '12')
#   same: === (True only if value and type are equal)

# truth check

function core_assert($v) { return _assert($v == true, $v); }
function core_assert_true($v) { return _assert($v === true, $v); }
function core_assert_false($v) { return _assert($v === false, $v); }
function core_assert_empty($v) { return _assert(empty($v), $v); }
function core_assert_zero($v) { return _assert($v === 0, $v); }
function core_assert_null($v) { return _assert(is_null($v), $v); }
function core_assert_pnull($v) { return _assert(is_pnull($v), $v); }

# type check

function core_assert_array($a) { return _assert(is_array($a), '$a'); }
function core_assert_bool($v) { return _assert(is_bool($v), $v); }
function core_assert_int($v) { return _assert(is_int($v), $v); }
function core_assert_float($v) { return _assert(is_float($v), $v); }
function core_assert_numeric($v) { return _assert(is_numeric($v), $v); }
function core_assert_string($v) { return _assert(is_string($v), $v); }
function core_assert_object($v) { return _assert(is_object($v), $v); }
function core_assert_resource($r) { return _assert(is_resource($r), strval($r)); }

# check values & comparison

function core_assert_equal($a, $b) { return _assert($a == $b, $a, $b); }
function core_assert_same($a, $b) { return _assert($a === $b, $a, $b); }
function core_assert_same_arrays($a1, $a2) { return _assert(_vhash($a1) === _vhash($a2), '$a1, $a2'); }
function core_assert_gt($a, $b) { return _assert($a > $b, $a, $b); }
function core_assert_gte($a, $b) { return _assert($a >= $b, $a, $b); }
function core_assert_lt($a, $b) { return _assert($a < $b, $a, $b); }
function core_assert_lte($a, $b) { return _assert($a <= $b, $a, $b); }

# constants & keys

function core_assert_defined($c) { return _assert(defined($c), $c); }
function core_assert_defined_and_equal($c, $v) { return _assert(defined($c), $c) && _assert(constant($c) == $v, $c, $v); }
function core_assert_defined_and_same($c, $v) { return _assert(defined($c), $c) && _assert(constant($c) === $v, $c, $v); }
function core_assert_key_exists($k, $a) { return _assert(array_key_exists($k, $a), $k, '$a'); }

# file system

function core_assert_file($p) { return _assert(is_file($p), $p); }
function core_assert_dir($p) { return _assert(is_dir($p), $p); }


# if $path is a script file - run all *_test functions in this file
# if $path is a folder - run all test scripts in this folder and its subfolders
function launch_tests($path)
{
    $path = rtrim($path, '\\/');

    # skip symlinks
    if (is_link($path)) return;

    if (is_dir($path))  // run all tests in the folder
    {
        $file_list = scandir($path, SCANDIR_SORT_NONE);  # order doesn't matter
        foreach($file_list as $file_name)
        {
            if ($file_name == '.' || $file_name == '..') continue;
            launch_tests($path.'/'.$file_name);  # recursion!
        }
        return;
    }

    # skip non PHP files
    if (is_file($path) && !str_ends_with($path, '.php')) return;

    # must be existing and readable
    if (!is_file($path))
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

