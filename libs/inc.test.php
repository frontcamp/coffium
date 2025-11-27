<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Testing API */


if (!defined('P_NULL'))
{
    trigger_error('Require system registry API library!', E_USER_ERROR);
    die();
}

global $_CORE_TEST_STATS;
$_CORE_TEST_STATS ??= [
    'files'     => 0,  // test files count
    'functions' => 0,  // test functions called (*_test())
    'asserts'   => 0,  // asserts runned
    'failed'    => 0,  // asserts failed
];

function core_test_stats_reset()
{
    global $_CORE_TEST_STATS;
    $_CORE_TEST_STATS = [
        'files'     => 0,
        'functions' => 0,
        'asserts'   => 0,
        'failed'    => 0,
    ];
}

function core_test_print_summary()
{
    global $_CORE_TEST_STATS;

    $passed = $_CORE_TEST_STATS['asserts'] - $_CORE_TEST_STATS['failed'];

    print '<p class="summary"><b>Summary</b>:'
         .' files: <b>'.$_CORE_TEST_STATS['files'].'</b>,'
         .' functions: <b>'.$_CORE_TEST_STATS['functions'].'</b>,'
         .' assertions: <b>'.$_CORE_TEST_STATS['asserts'].'</b>,'
         .' passed: <b>'.$passed.'</b>,'
         .' failed: <b>'.$_CORE_TEST_STATS['failed'].'</b></p>'.PHP_EOL;
}

function _vhash($v) { return md5(serialize($v)); }

function _assert($value, ...$args)
{
    global $_CORE_TEST_STATS;

    # reaching backtrace data
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $frame = $backtrace[1] ?? $backtrace[0];

    $file = $frame['file'] ?? 'unknown';
    $line = $frame['line'] ?? 0;
    $func = $frame['function'] ?? '{global}';

    $path_rel = abs_to_rel($file, PROJ_ROOT);

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
    $_CORE_TEST_STATS['asserts']++;
    if ($value === true)
    {
        if (isset($_REQUEST['verbose'])) {
            print("<p><b style='color:green;'>Passed:</b>"
                 ." {$func}(<b>$vdmp_prn</b>)"
                 ." from {$path_rel}:{$line}</p>\n");
        }
        return true;
    }
    else
    {
        $_CORE_TEST_STATS['failed']++;
        error_log("Assertion failed! {$func}($vdmp_raw)"
                 ." from {$path_rel}:{$line}");
        print("<p><b style='color:red;'>Failed:</b>"
             ." {$func}(<b>$vdmp_prn</b>)"
             ." from {$path_rel}:{$line}</p>\n");
        return false;
    }
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

function core_assert_const($c) { return _assert(defined($c), $c); }
function core_assert_const_and_equal($c, $v) { return _assert(defined($c), $c) && _assert(constant($c) == $v, $c, $v); }
function core_assert_const_and_same($c, $v) { return _assert(defined($c), $c) && _assert(constant($c) === $v, $c, $v); }
function core_assert_key_exists($k, $a) { return _assert(array_key_exists($k, $a), $k, '$a'); }

# file system

function core_assert_file($p) { return _assert(is_file($p), $p); }
function core_assert_dir($p) { return _assert(is_dir($p), $p); }


# if $path is a folder - run all test scripts in this folder and its subfolders
# if $path is a script file - run all *_test functions in this file
function launch_tests($path, $_top_call=true)
{
    global $_CORE_TEST_STATS;

    if ($_top_call) core_test_stats_reset();  # initialize stats

    $path = rtrim($path, '\\/');

    if (is_dir($path))
    {
        if (is_link($path)) return;  # skip symlinks

        # run all tests in the folder
        $file_list = scandir($path, SCANDIR_SORT_ASCENDING);
        foreach($file_list as $file_name)
        {
            if ($file_name == '.' || $file_name == '..') continue;
            launch_tests($path.'/'.$file_name, false);  # recursion
        }
    }
    else if (is_file($path))
    {
        if (!str_ends_with($path, '.php')) return;  # skip non PHP files

        $funcs = get_defined_functions()["user"];
        require($path);
        $tests = array_values(array_diff(get_defined_functions()["user"], $funcs));

        # leave public *_test functions only
        $tests = array_filter($tests, static function ($fn) {
            return !str_starts_with($fn, '_') && str_ends_with($fn, '_test');
        });

        if (empty($tests)) return;  # nothing to do here

        $_CORE_TEST_STATS['files']++;

        $path_rel = abs_to_rel($path, PROJ_ROOT);
        print "<hr>\n";
        print "<h2>Test module: <b>$path_rel</b></h2>\n";

        foreach($tests as $test_func)
        {
            $_CORE_TEST_STATS['functions']++;

            if (isset($_REQUEST['verbose'])) {
                print "<h3>Function: <b>$test_func()</b></h3>\n";
            }
            $test_func();  # run test(s)
        }
    }

    if ($_top_call) core_test_print_summary();
}

