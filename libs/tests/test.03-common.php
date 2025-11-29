<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Test core config */

/**
 * BOOL TYPE CONVERSIONS
 * ---------------------
 */

function core_common_bool_to_int_test()
{
    core_assert_same(bool_to_int(true), 1);
    core_assert_same(bool_to_int(false), 0);
}

function core_common_int_to_bool_test()
{
    core_assert_true(int_to_bool(1));
    core_assert_true(int_to_bool(5));

    core_assert_false(int_to_bool(0));
    core_assert_false(int_to_bool(-1));
}

function core_common_bool_to_onoff_test()
{
    core_assert_same(bool_to_onoff(true), 'On');
    core_assert_same(bool_to_onoff(false), 'Off');
}

function core_common_bool_to_yesno_test()
{
    core_assert_same(bool_to_yesno(true), 'Yes');
    core_assert_same(bool_to_yesno(false), 'No');
}

function core_common_some_to_onoff_test()
{
    # truthy values
    core_assert_same(some_to_onoff(true), 'On');
    core_assert_same(some_to_onoff(1), 'On');
    core_assert_same(some_to_onoff(2), 'On');
    core_assert_same(some_to_onoff('1'), 'On');
    core_assert_same(some_to_onoff('true'), 'On');
    core_assert_same(some_to_onoff([1]), 'On');

    # falsy values
    core_assert_same(some_to_onoff(false), 'Off');
    core_assert_same(some_to_onoff(0), 'Off');
    core_assert_same(some_to_onoff('0'), 'Off');
    core_assert_same(some_to_onoff(''), 'Off');
    core_assert_same(some_to_onoff(null), 'Off');
    core_assert_same(some_to_onoff([]), 'Off');
}

function core_common_some_to_yesno_test()
{
    # truthy values
    core_assert_same(some_to_yesno(true), 'Yes');
    core_assert_same(some_to_yesno(1), 'Yes');
    core_assert_same(some_to_yesno('foo'), 'Yes');

    # falsy values
    core_assert_same(some_to_yesno(false), 'No');
    core_assert_same(some_to_yesno(0), 'No');
    core_assert_same(some_to_yesno('0'), 'No');
    core_assert_same(some_to_yesno(''), 'No');
    core_assert_same(some_to_yesno(null), 'No');
}

/**
 * CHECKING NUMERIC TYPES
 * ----------------------
 */

function core_common_is_float_or_int_test()
{
    # verify that native ints and floats are accepted
    core_assert_true(is_float_or_int(0));
    core_assert_true(is_float_or_int(-10));
    core_assert_true(is_float_or_int(3.14));

    # verify that non-numeric scalars are rejected
    core_assert_false(is_float_or_int('1'));
    core_assert_false(is_float_or_int('1.0'));
    core_assert_false(is_float_or_int('foo'));
    core_assert_false(is_float_or_int(null));
}

function core_common_is_int_or_float_test()
{
    core_assert_true(is_int_or_float(0));
    core_assert_true(is_int_or_float(123));
    core_assert_true(is_int_or_float(2.5));

    core_assert_false(is_int_or_float('123'));
    core_assert_false(is_int_or_float('2.5'));
    core_assert_false(is_int_or_float([]));
}

function core_common_is_int_like_test()
{
    # verify native integers are always treated as int-like
    core_assert_true(is_int_like(0));
    core_assert_true(is_int_like(42));
    core_assert_true(is_int_like(-123));

    # verify decimal-digit strings with optional sign are int-like
    core_assert_true(is_int_like('0'));
    core_assert_true(is_int_like('007'));
    core_assert_true(is_int_like('-5'));
    core_assert_true(is_int_like('+42'));

    # verify that non-digit strings and edge cases are rejected
    core_assert_false(is_int_like(''));
    core_assert_false(is_int_like('12.3'));
    core_assert_false(is_int_like('3.14'));
    core_assert_false(is_int_like('abc'));
    core_assert_false(is_int_like('+'));
    core_assert_false(is_int_like('-'));
    core_assert_false(is_int_like('+-1'));
    core_assert_false(is_int_like('0x10'));
}

function core_common_is_decimal_test()
{
    # verify that any string containing "." is treated as decimal-like
    core_assert_true(is_decimal('0.0'));
    core_assert_true(is_decimal('.5'));
    core_assert_true(is_decimal('123.'));
    core_assert_true(is_decimal('abc.def'));

    # verify that strings without "." are rejected
    core_assert_false(is_decimal(''));
    core_assert_false(is_decimal('123'));
    core_assert_false(is_decimal('1,2'));
}

/**
 * NUMERICAL UTILITIES
 * -------------------
 */

function core_common_ntrim_test()
{
    # clamp integer values to the closed [min, max] interval
    core_assert_same(ntrim(5, 0, 10), 5);
    core_assert_same(ntrim(-5, 0, 10), 0);
    core_assert_same(ntrim(15, 0, 10), 10);

    # clamp float values and preserve fractional part within range
    $v = ntrim(1.5, 0.5, 2.0);
    core_assert_float($v);
    core_assert_same($v, 1.5);

    core_assert_same(ntrim(0.1, 0.5, 2.0), 0.5);
    core_assert_same(ntrim(3.0, 0.5, 2.0), 2.0);
}

/**
 * COLLECTIONS / ARRAYS
 * --------------------
 */

function core_common_empty_array_test()
{
    # 1) Basic semantics: fresh empty array instance on each call
    $a =& empty_array();
    $b =& empty_array();

    core_assert_array($a);
    core_assert_array($b);
    core_assert_empty($a);
    core_assert_empty($b);

    $a['key'] = 'value';

    core_assert_key_exists('key', $a);
    core_assert_empty($b);  // $b must still be a fresh empty array

    # helper: mutate array passed by reference
    function _common_empty_array_fill(array &$a): void
    {
        $a['marker'][] = 'x';
    }

    # helper: forward reference through another function
    function _common_empty_array_proxy(array &$a): void
    {
        _common_empty_array_fill($a);
    }

    # 2) Mutability through a function with by-reference parameter
    _common_empty_array_fill($a);
    core_assert_key_exists('marker', $a);
    core_assert_same(count($a['marker']), 1);

    # 3) Key feature: use empty_array() directly as a by-reference argument
    #
    # If empty_array() does NOT return by reference, this call MUST fail
    # in modern PHP with a "must be passed by reference" error.
    # Successful execution here implicitly confirms reference semantics.
    _common_empty_array_fill(empty_array());

    # 4) New calls after using temporary must still start from a clean empty array
    $c =& empty_array();
    core_assert_array($c);
    core_assert_empty($c);

    # 5) Forwarding the same reference through multiple functions
    _common_empty_array_proxy($c);
    core_assert_key_exists('marker', $c);
    core_assert_same(count($c['marker']), 1);
}

/**
 * WORKING WITH DATA FILES
 * -----------------------
 */

function core_common_file_get_data_test()
{
    # prepare test directory and two data files under TEMP_ROOT
    $base_dir = path_normjoin(TEMP_ROOT, 'core-common-file-data-test');
    if (!is_dir($base_dir)) mkdir($base_dir, 0777, true);

    $file_default = path_normjoin($base_dir, 'get-default.php');
    $file_named   = path_normjoin($base_dir, 'get-named.php');

    # write fixtures using file_put_data() with default and custom variable names
    $data1 = array('a' => 1, 'b' => array('x', 'y'));
    file_put_data($file_default, $data1);  # uses default 'DATA'

    $data2 = array('foo' => 'bar');
    file_put_data($file_named, $data2, 'VALUE');

    core_assert_file($file_default);
    core_assert_file($file_named);

    # verify reading with default variable name
    $loaded1 = file_get_data($file_default);
    core_assert_same_arrays($loaded1, $data1);

    # verify reading with explicit custom variable name
    $loaded2 = file_get_data($file_named, 'VALUE');
    core_assert_same_arrays($loaded2, $data2);

    # cleanup test files
    rmtree($base_dir);
}

function core_common_file_put_data_test()
{
    # verify that file_put_data() writes valid PHP assignment with given var name
    $base_dir = path_normjoin(TEMP_ROOT, 'core-common-file-data-test');
    if (!is_dir($base_dir)) mkdir($base_dir, 0777, true);

    $file1 = path_normjoin($base_dir, 'put-default.php');
    $file2 = path_normjoin($base_dir, 'put-custom.php');

    # write using default and explicit variable names
    $data1 = array(1, 2, 3);
    file_put_data($file1, $data1);  # default 'DATA'

    $data2 = array('nested' => array('k' => 'v'));
    file_put_data($file2, $data2, 'STORAGE');

    core_assert_file($file1);
    core_assert_file($file2);

    # include generated files and verify that variables are defined and equal
    unset($DATA, $STORAGE);

    include $file1;
    include $file2;

    core_assert_array($DATA);
    core_assert_same_arrays($DATA, $data1);

    core_assert_array($STORAGE);
    core_assert_same_arrays($STORAGE, $data2);

    # cleanup test files
    rmtree($base_dir);
}

/**
 * FYLE SYSTEM & PATHS
 * -------------------
 */

function core_common_path_normalize_test()
{
    $ds = DIRECTORY_SEPARATOR;

    # verify that empty string stays empty (no "." normalization)
    core_assert_same(path_normalize(''), '');

    # verify collapsing duplicate separators and unifying slashes
    $norm = path_normalize('foo\\bar//baz///');
    core_assert_same($norm, 'foo'.$ds.'bar'.$ds.'baz');

    # verify normalisation of root path
    $root = path_normalize('/');
    core_assert_same($root, $ds);

    # verify that "." and ".." segments are preserved as-is
    $p = path_normalize('/a/./b/../c');
    $segments = explode($ds, trim($p, '/\\'));
    core_assert_same_arrays($segments, array('a', '.', 'b', '..', 'c'));
}

function core_common_path_join_test()
{
    $ds = DIRECTORY_SEPARATOR;

    # verify joining of simple relative segments
    core_assert_same(
        path_join('foo', 'bar'),
        'foo'.$ds.'bar'
    );

    core_assert_same(
        path_join('foo', 'bar', 'baz'),
        'foo'.$ds.'bar'.$ds.'baz'
    );

    # verify trimming of slashes and ignoring empty segments
    core_assert_same(
        path_join('', 'foo/', '', 'bar'),
        'foo'.$ds.'bar'
    );

    # verify that absolute segment resets all previous parts
    core_assert_same(
        path_join('foo', '/bar'),
        '/bar'
    );
}

function core_common_path_normjoin_test()
{
    $ds = DIRECTORY_SEPARATOR;

    # verify join+normalize for simple relative segments
    $p = path_normjoin('foo', 'bar', 'baz');
    core_assert_same($p, 'foo'.$ds.'bar'.$ds.'baz');

    # verify behavior when absolute segment appears in the middle
    $p2 = path_normjoin('foo//', '/bar');
    core_assert_same($p2, path_normalize('/bar'));

    # verify that empty argument list produces an empty string
    core_assert_same(path_normjoin(), '');
}

function core_common_abs_to_rel_test()
{
    $ds = DIRECTORY_SEPARATOR;

    # verify that project root maps to a single separator
    $rel_root = abs_to_rel(PROJ_ROOT);
    core_assert_same($rel_root, $ds);

    # verify that a direct child directory becomes a simple relative path
    $libs_root = path_normjoin(PROJ_ROOT, 'libs');
    $rel_libs  = abs_to_rel($libs_root);
    core_assert_same($rel_libs, 'libs');

    # verify that nested directory becomes a relative path with OS separators
    $tests_root = path_normjoin(PROJ_ROOT, 'libs', 'tests');
    $rel_tests  = abs_to_rel($tests_root);
    core_assert_same($rel_tests, 'libs'.$ds.'tests');

    # verify that clearly external (relative) paths are rejected
    $thrown1 = false;
    try {
        abs_to_rel('path-outside-project-root');
    } catch (InvalidArgumentException $e) {
        $thrown1 = true;
    }
    core_assert_true($thrown1);

    # verify that paths sharing only a string prefix with PROJ_ROOT are rejected
    $fake = PROJ_ROOT.'_suffix';
    $thrown2 = false;
    try {
        abs_to_rel($fake);
    } catch (InvalidArgumentException $e) {
        $thrown2 = true;
    }
    core_assert_true($thrown2);
}

function core_common_scandir_advanced_test()
{
    # prepare isolated temporary directory tree with folders and files
    $base_dir = path_normjoin(TEMP_ROOT, 'tests-common-scandir');

    rmtree($base_dir);  # ensure clean state
    mkdir($base_dir, 0777, true);

    $dir_b = path_normjoin($base_dir, 'b-sub');  # intentionally unordered
    $dir_a = path_normjoin($base_dir, 'a-sub');
    mkdir($dir_b, 0777, true);
    mkdir($dir_a, 0777, true);

    $file1 = path_normjoin($base_dir, 'file10.txt');
    $file2 = path_normjoin($base_dir, 'file2.txt');
    file_put_contents($file1, 'x');
    file_put_contents($file2, 'x');

    # verify that folders go first, then files, each group natsorted
    $items = scandir_advanced($base_dir);
    core_assert_same_arrays($items, array('a-sub', 'b-sub', 'file2.txt', 'file10.txt'));

    rmtree($base_dir);
}

function core_common_rmtree_test()
{
    # create nested directory tree with several files
    $base_dir = path_normjoin(TEMP_ROOT, 'tests-common-rmtree');
    rmtree($base_dir);  # clean up any leftovers from previous runs

    $level1 = path_normjoin($base_dir, 'level1');
    $level2 = path_normjoin($level1, 'level2');

    mkdir($level2, 0777, true);

    file_put_contents(path_normjoin($base_dir, 'root.txt'), 'root');
    file_put_contents(path_normjoin($level1, 'l1.txt'), 'l1');
    file_put_contents(path_normjoin($level2, 'l2.txt'), 'l2');

    core_assert_dir($base_dir);

    # verify that rmtree() removes the whole tree
    rmtree($base_dir);
    core_assert_false(is_dir($base_dir));

    # verify that repeated calls on non-existing path are safe no-ops
    rmtree($base_dir);
    core_assert_false(is_dir($base_dir));
}

/**
 * HTTP / WEB UTILITIES
 * --------------------
 */

function core_common_get_client_ip_test()
{
    # backup original $_SERVER to avoid side effects between tests
    $backup = $_SERVER;

    # verify fallback behavior when no related headers are present
    unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);
    core_assert_same(get_client_ip(), '0.0.0.0');

    # verify REMOTE_ADDR only
    $_SERVER['REMOTE_ADDR'] = '192.0.2.45';
    core_assert_same(get_client_ip(), '192.0.2.45');

    # verify that HTTP_X_FORWARDED_FOR is used and first IP is taken
    unset($_SERVER['HTTP_CLIENT_IP']);
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 10.0.0.1';
    core_assert_same(get_client_ip(), '203.0.113.1');

    # verify that HTTP_CLIENT_IP has the highest priority
    $_SERVER['HTTP_CLIENT_IP'] = '198.51.100.25';
    core_assert_same(get_client_ip(), '198.51.100.25');

    # restore original server environment
    $_SERVER = $backup;
}

function core_common_redirect_test()
{
    # verify public signature of redirect() without executing it (no die())
    $ref = new ReflectionFunction('redirect');

    $params = $ref->getParameters();
    core_assert_same(count($params), 3);

    # verify that "location" is a required first parameter
    core_assert_same($params[0]->getName(), 'location');
    core_assert_false($params[0]->isOptional());

    # verify that "status" is optional and defaults to 302
    core_assert_same($params[1]->getName(), 'status');
    core_assert_true($params[1]->isOptional());
    core_assert_same($params[1]->getDefaultValue(), 302);

    # verify that "x_redirect_by" is optional and defaults to CORE_NAME
    core_assert_same($params[2]->getName(), 'x_redirect_by');
    core_assert_true($params[2]->isOptional());
    core_assert_same($params[2]->getDefaultValue(), CORE_NAME);
}

function core_common_reload_test()
{
    # verify that reload() helper exists and has no parameters
    $ref = new ReflectionFunction('reload');
    core_assert_same($ref->getNumberOfParameters(), 0);
}

