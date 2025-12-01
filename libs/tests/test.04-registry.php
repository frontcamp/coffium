<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;

/**
 * System registry tests for /libs/inc.registry.php
 * New version: explicit logic + SysRegException-based error handling.
 */

/**
 * Basic P_NULL and is_pnull() semantics.
 */
function core_registry_pnull_test()
{
    # Check that P_NULL is defined and is_pnull() exists and it works
    core_assert_true(defined('P_NULL'));
    core_assert_true(function_exists('is_pnull'));
    core_assert_true(is_pnull(P_NULL));

    # P_NULL must be different from common "empty" values
    core_assert_false(is_pnull(null));
    core_assert_false(is_pnull(0));
    core_assert_false(is_pnull(''));
    core_assert_false(is_pnull(false));

    # is_pnull() must not treat arbitrary values as P_NULL
    core_assert_false(is_pnull('P_NULL'));
}

/**
 * Registry stack: store/restore/set + sys_hash() and error handling.
 */
function core_registry_store_restore_test()
{
    global $SYS, $_SYS_STACK;

    # Backup originals to restore at the end
    $orig_SYS   = $SYS;
    $orig_STACK = $_SYS_STACK;

    # Normal store/restore roundtrip
    $SYS = array('a' => 1, 'b' => 2);
    $_SYS_STACK = array();

    $hash_before = sys_hash();

    # Store current registry and check stack size
    $stored = store_system_registry();
    core_assert_same_arrays($SYS, $stored);
    core_assert_same(1, count($_SYS_STACK));

    # Modify registry and ensure hash changes
    $SYS['c'] = 3;
    $hash_after = sys_hash();
    core_assert_true($hash_before !== $hash_after);

    # Restore and verify state and hash
    $restored_flag = restore_system_registry();
    core_assert_true($restored_flag);
    core_assert_same_arrays(array('a' => 1, 'b' => 2), $SYS);
    core_assert_same($hash_before, sys_hash());
    core_assert_same(0, count($_SYS_STACK));

    # Multiple restore() on empty stack must return false without changing registry
    $before = $SYS;
    core_assert_false(restore_system_registry());
    core_assert_same_arrays($before, $SYS);

    # set_system_registry() with auto_store must push previous registry to stack
    $_SYS_STACK = array();
    $SYS = array('x' => 10);
    $result = set_system_registry(array('y' => 20), true);
    core_assert_same_arrays(array('y' => 20), $result);
    core_assert_same_arrays(array('x' => 10), $_SYS_STACK[0]);

    # set_system_registry() with auto_store=false must not touch stack
    $_SYS_STACK = array();
    $SYS = array('p' => 1);
    $result = set_system_registry(array('q' => 2), false);
    core_assert_same_arrays(array('q' => 2), $result);
    core_assert_same(0, count($_SYS_STACK));

    # Error: corrupted registry (E_REG_CORRUPT) for sys_hash()
    $SYS = 'not-an-array';
    try {
        sys_hash();
        core_assert_true(false);  # must not reach this line
    } catch (SysRegException $e) {
        core_assert_true(str_starts_with($e->getMessage(), E_REG_CORRUPT));
    }

    # Error: corrupted stack (E_STACK_CORRUPT) for store_system_registry()
    $SYS = array('ok' => true);
    $_SYS_STACK = 'not-an-array';
    try {
        store_system_registry();
        core_assert_true(false);  # must not reach this line
    } catch (SysRegException $e) {
        core_assert_true(str_starts_with($e->getMessage(), E_STACK_CORRUPT));
    }

    # Error: corrupted stack (E_STACK_CORRUPT) for restore_system_registry()
    $_SYS_STACK = 'still-not-an-array';
    try {
        restore_system_registry();
        core_assert_true(false);  # must not reach this line
    } catch (SysRegException $e) {
        core_assert_true(str_starts_with($e->getMessage(), E_STACK_CORRUPT));
    }

    # Error: invalid item on stack (E_STACK_INVALID) when restored value is not array
    $SYS = array('valid' => true);
    $_SYS_STACK = array('not-array-on-stack');
    try {
        restore_system_registry();
        core_assert_true(false);  # must not reach this line
    } catch (SysRegException $e) {
        core_assert_true(str_starts_with($e->getMessage(), E_STACK_INVALID));
    }

    # Restore original globals
    $SYS        = $orig_SYS;
    $_SYS_STACK = $orig_STACK;
}

/**
 * Top-level API basics: sys(), sys_add(), sys_set(), sys_get(), sys_check/equal/same, sys_keys/values.
 */
function core_registry_sys_basic_test()
{
    store_system_registry();
    sys_clear();

    # sys_add() creates keys and respects overwrite flag
    sys_add('a', 'A');
    sys_add('b', 'B');
    core_assert_same(2, sys_count());
    core_assert_same('A', sys_get('a'));
    core_assert_same('B', sys_get('b'));
    core_assert_pnull(sys_get('missing'));

    sys_add('a', 'A2', false);
    core_assert_same('A', sys_get('a'));

    sys_add('a', 'A2');  # default overwrite = true
    core_assert_same('A2', sys_get('a'));

    # sys_set() is alias to sys_add()
    sys_set('c', 123);
    core_assert_true(sys_has_key('c'));
    core_assert_same(123, sys_get('c'));

    # sys(): shortcut get/set semantics with P_NULL
    sys('d', 'D');
    core_assert_same('D', sys('d'));
    core_assert_same('D', sys('d', P_NULL));
    core_assert_pnull(sys('no_such_key'));
    core_assert_pnull(sys('no_such_key', P_NULL));

    # sys_check / sys_equal / sys_same strict vs non-strict
    sys_set('num', '10');
    core_assert_true(sys_check('num', '10', true));
    core_assert_true(sys_equal('num', 10));   # non-strict
    core_assert_false(sys_same('num', 10));   # strict

    # sys_has_key() / sys_has_value() (strict by default)
    core_assert_true(sys_has_key('a'));
    core_assert_false(sys_has_key('___nope___'));

    sys_set('int_val', 10);
    sys_set('str_val', '10');

    core_assert_true(sys_has_value(10));             # strict match to int
    core_assert_true(sys_has_value('10'));           # strict match to string
    core_assert_false(sys_has_value(11));            # no such value
    core_assert_true(sys_has_value(10, false));      # still true in non-strict mode
    core_assert_true(sys_has_value('10', false));    # also true in non-strict mode

    # sys_keys() / sys_values() must reflect current content
    $keys   = sys_keys();
    $values = sys_values();
    core_assert_true(in_array('a', $keys, true));
    core_assert_true(in_array('b', $keys, true));
    core_assert_true(in_array('c', $keys, true));
    core_assert_true(in_array('D', $values, true));

    restore_system_registry();
}

/**
 * Registry as list: push/pop, shift/unshift, unique ops, remove/remove_all, search.
 */
function core_registry_sys_array_ops_test()
{
    store_system_registry();
    sys_clear();

    # push / values / pop
    $count = sys_push('a', 'b');
    core_assert_same(2, $count);
    core_assert_same_arrays(array('a', 'b'), sys_values());

    $popped = sys_pop();
    core_assert_same('b', $popped);
    core_assert_same_arrays(array('a'), sys_values());

    # unshift / shift
    sys_unshift('x');
    core_assert_same_arrays(array('x', 'a'), sys_values());

    $shifted = sys_shift();
    core_assert_same('x', $shifted);
    core_assert_same_arrays(array('a'), sys_values());

    # push_unique / unshift_unique with strict semantics
    sys_push_unique('a', 'b', 'c');  # 'a' already exists
    core_assert_same_arrays(array('a', 'b', 'c'), sys_values());

    sys_unshift_unique('c', 'd');    # 'c' already exists, 'd' is new
    core_assert_same_arrays(array('d', 'a', 'b', 'c'), sys_values());

    # remove() must remove first occurrence and return key, remove_all() must remove all
    $key = sys_remove('a');
    core_assert_true($key !== false);
    core_assert_false(sys_has_value('a'));

    sys_push('x', 'x', 'x');
    $deleted = sys_remove_all('x');
    core_assert_same(3, $deleted);
    core_assert_false(sys_has_value('x'));

    # search() existing and missing values
    $key_d = sys_search('d');  # existing
    core_assert_true($key_d !== false);

    $key_missing = sys_search('___no_value___');  # missing
    core_assert_false($key_missing);

    restore_system_registry();
}

/**
 * sys_opt_init()/sys_opt_exists() behavior with absent/array/scalar branches and overwrite flag.
 */
function core_registry_sys_opt_init_exists_test()
{
    store_system_registry();
    sys_clear();

    # Absent option: sys_opt_exists() must return false
    core_assert_false(sys_opt_exists('opts'));

    # sys_opt_init() on absent option must create empty array and return true
    $created = sys_opt_init('opts');
    core_assert_true($created);
    core_assert_true(sys_opt_exists('opts'));
    core_assert_array(sys_get('opts'));
    core_assert_same(0, sys_opt_count('opts'));

    # sys_opt_init() on existing array must be safe for both overwrite=true and overwrite=false
    core_assert_true(sys_opt_init('opts', false));
    core_assert_true(sys_opt_exists('opts'));

    # Scalar branch: overwrite=true must convert it to array
    sys_set('scalar_branch', 42);
    core_assert_false(sys_opt_exists('scalar_branch'));
    $converted = sys_opt_init('scalar_branch', true);
    core_assert_true($converted);
    core_assert_true(sys_opt_exists('scalar_branch'));
    core_assert_array(sys_get('scalar_branch'));

    # Scalar branch: overwrite=false must throw E_OPT_CORRUPT
    sys_set('bad_branch', 100);
    try {
        sys_opt_init('bad_branch', false);
        core_assert_true(false);  # must not reach this line
    } catch (SysRegException $e) {
        core_assert_true(str_starts_with($e->getMessage(), E_OPT_CORRUPT));
    }

    # sys_opt_exists() must be sensitive to type (false for scalar, true for array)
    sys_set('another_scalar', 'x');
    core_assert_false(sys_opt_exists('another_scalar'));
    sys_opt_init('another_scalar');
    core_assert_true(sys_opt_exists('another_scalar'));

    restore_system_registry();
}

/**
 * Basic sys_opt_* operations, strict/non-strict semantics and error cases for missing/scalar options.
 */
function core_registry_sys_opt_basic_test()
{
    store_system_registry();
    sys_clear();

    # sys_opt(): set via shortcut
    sys_opt('colors', 'bg', '#fff');
    core_assert_true(sys_has_key('colors'));
    core_assert_array(sys_get('colors'));
    core_assert_same_arrays(array('bg' => '#fff'), sys_get('colors'));

    # sys_opt_get(): existing and missing key
    core_assert_same('#fff', sys_opt_get('colors', 'bg'));
    core_assert_pnull(sys_opt_get('colors', 'missing'));

    # sys_opt_add(): overwrite control
    $result1 = sys_opt_add('colors', 'bg', '#000', false);
    core_assert_true($result1 === false);  # no overwrite
    core_assert_same('#fff', sys_opt_get('colors', 'bg'));

    $result2 = sys_opt_add('colors', 'bg', '#000', true);
    core_assert_true($result2);
    core_assert_same('#000', sys_opt_get('colors', 'bg'));

    # sys_opt_set(): alias to sys_opt_add()
    $result3 = sys_opt_set('colors', 'fg', '#111');
    core_assert_true($result3);
    core_assert_same('#111', sys_opt_get('colors', 'fg'));

    # has_key / has_value / check / equal / same with strict semantics
    core_assert_true(sys_opt_has_key('colors', 'fg'));
    core_assert_false(sys_opt_has_key('colors', 'nope'));

    core_assert_true(sys_opt_has_value('colors', '#000'));
    core_assert_true(sys_opt_check('colors', 'fg', '#111'));
    core_assert_true(sys_opt_equal('colors', 'fg', '#111'));
    core_assert_true(sys_opt_same('colors', 'fg', '#111'));
    core_assert_false(sys_opt_same('colors', 'fg', 111));

    # count / keys / values
    core_assert_same(2, sys_opt_count('colors'));
    core_assert_same_arrays(array('bg', 'fg'), sys_opt_keys('colors'));
    core_assert_same_arrays(array('#000', '#111'), sys_opt_values('colors'));

    # sys_opt_add() on absent option must create branch
    sys_opt_add('sizes', 'small', 'S');
    core_assert_true(sys_opt_exists('sizes'));
    core_assert_same('S', sys_opt_get('sizes', 'small'));

    # Missing option: functions that require existing array must throw E_OPT_CORRUPT
    $failing_calls = array(
        'count'      => function () { sys_opt_count('missing_opt'); },
        'keys'       => function () { sys_opt_keys('missing_opt'); },
        'values'     => function () { sys_opt_values('missing_opt'); },
        'get'        => function () { sys_opt_get('missing_opt', 'k'); },
        'has_key'    => function () { sys_opt_has_key('missing_opt', 'k'); },
        'has_value'  => function () { sys_opt_has_value('missing_opt', 'v'); },
        'insert'     => function () { sys_opt_insert('missing_opt', 0, 'v'); },
        'pop'        => function () { sys_opt_pop('missing_opt'); },
        'shift'      => function () { sys_opt_shift('missing_opt'); },
        'clear'      => function () { sys_opt_clear('missing_opt'); },
        'delete'     => function () { sys_opt_delete('missing_opt', 'k'); },
        'remove'     => function () { sys_opt_remove('missing_opt', 'v'); },
        'remove_all' => function () { sys_opt_remove_all('missing_opt', 'v'); },
        'search'     => function () { sys_opt_search('missing_opt', 'v'); },
    );

    # Each of the above must throw SysRegException with E_OPT_CORRUPT prefix
    foreach ($failing_calls as $name => $callable) {
        try {
            $callable();
            core_assert_true(false);  # must not reach this line
        } catch (SysRegException $e) {
            core_assert_true(str_starts_with($e->getMessage(), E_OPT_CORRUPT));
        }
    }

    # Scalar option: many operations must also trigger E_OPT_CORRUPT
    sys_set('scalar_opt', 42);
    try {
        sys_opt_count('scalar_opt');
        core_assert_true(false);
    } catch (SysRegException $e) {
        core_assert_true(str_starts_with($e->getMessage(), E_OPT_CORRUPT));
    }

    # sys_opt_add() on scalar with overwrite=true must convert it to array
    $ok = sys_opt_add('scalar_opt', 'key', 'val', true);
    core_assert_true($ok);
    core_assert_true(sys_opt_exists('scalar_opt'));
    core_assert_same('val', sys_opt_get('scalar_opt', 'key'));

    restore_system_registry();
}

/**
 * Array-like operations for sys_opt_*: push/pop, shift/unshift,
 * unique variants, insert (including negative and out-of-range),
 * remove/remove_all, search, clear.
 */
function core_registry_sys_opt_array_ops_test()
{
    $opt = 'list';

    store_system_registry();
    sys_clear();

    # push / values / pop
    sys_opt_push($opt, 'a', 'b');
    core_assert_same_arrays(array('a', 'b'), sys_opt_values($opt));

    $popped = sys_opt_pop($opt);
    core_assert_same('b', $popped);
    core_assert_same_arrays(array('a'), sys_opt_values($opt));

    # unshift / shift
    sys_opt_unshift($opt, 'x');
    core_assert_same_arrays(array('x', 'a'), sys_opt_values($opt));

    $shifted = sys_opt_shift($opt);
    core_assert_same('x', $shifted);
    core_assert_same_arrays(array('a'), sys_opt_values($opt));

    # push_unique / unshift_unique with strict semantics
    sys_opt_push_unique($opt, 'a', 'b', 'c');  # 'a' already exists
    core_assert_same_arrays(array('a', 'b', 'c'), sys_opt_values($opt));

    sys_opt_unshift_unique($opt, 'c', 'd');    # 'c' already exists, 'd' is new
    core_assert_same_arrays(array('d', 'a', 'b', 'c'), sys_opt_values($opt));

    # Insert: position inside range
    sys_opt_insert($opt, 2, 'X');  # d, a, X, b, c
    core_assert_same_arrays(array('d', 'a', 'X', 'b', 'c'), sys_opt_values($opt));

    # Insert: negative index (before last element)
    sys_opt_insert($opt, -1, 'Y');  # d, a, X, b, Y, c
    core_assert_same_arrays(array('d', 'a', 'X', 'b', 'Y', 'c'), sys_opt_values($opt));

    # Insert: negative index beyond range must clamp to start
    sys_opt_insert($opt, -100, 'S');  # S must be inserted at position 0
    $vals = sys_opt_values($opt);
    core_assert_same('S', $vals[0]);

    # Insert: index out of range — append to the end
    $before = sys_opt_count($opt);
    sys_opt_insert($opt, 100, 'Z');
    core_assert_same($before + 1, sys_opt_count($opt));
    core_assert_same('Z', sys_opt_pop($opt));  # must be at the end

    # remove / remove_all
    $deleted_key = sys_opt_remove($opt, 'X');
    core_assert_true($deleted_key !== false);
    core_assert_false(sys_opt_has_value($opt, 'X'));

    sys_opt_push($opt, 'dup', 'dup', 'other', 'dup');
    $deleted = sys_opt_remove_all($opt, 'dup');
    core_assert_same(3, $deleted);
    core_assert_false(sys_opt_has_value($opt, 'dup'));

    # search existing and missing values
    core_assert_same(0, sys_opt_search($opt, 'S'));
    core_assert_false(sys_opt_search($opt, '___no_value___'));

    # clear must empty option but keep it as array
    sys_opt_clear($opt);
    core_assert_same(0, sys_opt_count($opt));
    core_assert_array(sys_get($opt));

    restore_system_registry();
}

/**
 * P_NULL semantics in sys() and sys_opt() shortcuts (read vs write mode).
 */
function core_registry_pnull_usage_test()
{
    store_system_registry();
    sys_clear();

    # sys(): P_NULL => read mode
    sys_set('x', 'value');

    $v1 = sys('x');
    $v2 = sys('x', P_NULL);

    core_assert_same('value', $v1);
    core_assert_same('value', $v2);

    core_assert_pnull(sys('missing'));
    core_assert_pnull(sys('missing', P_NULL));

    # sys_opt(): same semantics
    sys_opt_set('opts', 'key', 10);

    $o1 = sys_opt('opts', 'key');
    $o2 = sys_opt('opts', 'key', P_NULL);

    core_assert_same(10, $o1);
    core_assert_same(10, $o2);

    core_assert_pnull(sys_opt('opts', 'missing'));
    core_assert_pnull(sys_opt('opts', 'missing', P_NULL));

    restore_system_registry();
}

/**
 * Global error handling: E_REG_CORRUPT and E_OPT_CORRUPT via top-level and options API.
 */
function core_registry_error_handling_test()
{
    global $SYS;

    # Backup registry
    $orig_SYS = $SYS;

    # E_REG_CORRUPT from top-level API (sys_count())
    $SYS = 'not-an-array';
    try {
        sys_count();
        core_assert_true(false);  # must not reach this line
    } catch (SysRegException $e) {
        core_assert_true(str_starts_with($e->getMessage(), E_REG_CORRUPT));
    }

    # Restore registry to valid array
    $SYS = array();

    # E_REG_CORRUPT via options API (sys_opt_exists())
    $SYS = 'still-not-an-array';
    try {
        sys_opt_exists('any_opt');
        core_assert_true(false);  # must not reach this line
    } catch (SysRegException $e) {
        core_assert_true(str_starts_with($e->getMessage(), E_REG_CORRUPT));
    }

    # Valid registry again
    $SYS = array();

    # E_OPT_CORRUPT when using options API on scalar branch
    sys_set('scalar_opt', 100);
    try {
        sys_opt_has_value('scalar_opt', 100);
        core_assert_true(false);  # must not reach this line
    } catch (SysRegException $e) {
        core_assert_true(str_starts_with($e->getMessage(), E_OPT_CORRUPT));
    }

    # Restore original registry
    $SYS = $orig_SYS;
}

