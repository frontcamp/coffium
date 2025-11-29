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

/**
 * FYLE SYSTEM & PATHS
 * -------------------
 */


/**
 * HTTP / WEB UTILITIES
 * --------------------
 */
















































