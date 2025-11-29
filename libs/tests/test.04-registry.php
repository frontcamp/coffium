<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* System registry tests */

/**
 * Basic check of P_NULL and is_pnull().
 */
function core_registry_pnull_test()
{
    core_assert_true(defined('P_NULL'));

    # P_NULL must be recognized by is_pnull()
    core_assert_true(is_pnull(P_NULL));

    # Regular values are not P_NULL
    core_assert_false(is_pnull(null));
    core_assert_false(is_pnull(0));
    core_assert_false(is_pnull('P_NULL'));
}

/**
 * Registry stack: store_system_registry() / restore_system_registry() / set_system_registry().
 */
function core_registry_store_restore_test()
{
    global $_SYS_STACK;

    $initial_stack_depth = isset($_SYS_STACK) ? count($_SYS_STACK) : 0;
    $orig_hash = sys_hash();

    # save current registry
    store_system_registry();
    core_assert_same($initial_stack_depth + 1, count($_SYS_STACK));

    # work in isolated registry
    sys_clear();
    core_assert_zero(sys_count());

    sys('test.key', 'value');
    core_assert_same('value', sys_get('test.key'));
    core_assert_same(1, sys_count());

    # restore original state
    $restored = restore_system_registry();
    core_assert_true($restored);

    core_assert_same($orig_hash, sys_hash());
    core_assert_same($initial_stack_depth, count($_SYS_STACK));
}

/**
 * Basic API: sys(), sys_get(), sys_add(), sys_set(), sys_check()/sys_equal()/sys_same,
 * and also sys_has_key()/sys_has_value().
 */
function core_registry_sys_shortcuts_test()
{
    store_system_registry();
    sys_clear();

    # sys(): set/get
    sys('a', 'A');
    sys('b', 'B');

    core_assert_same(2, sys_count());
    core_assert_same('A', sys_get('a'));
    core_assert_same('B', sys_get('b'));
    core_assert_pnull(sys_get('missing'));

    # sys_add(): without overwrite and with overwrite
    sys_add('a', 'A2', false);
    core_assert_same('A', sys_get('a'));

    sys_add('a', 'A2');  # overwrite by default
    core_assert_same('A2', sys_get('a'));

    # sys_set(): alias to sys_add()
    sys_set('c', 123);
    core_assert_true(sys_has_key('c'));
    core_assert_same(123, sys_get('c'));

    # sys_check / sys_equal / sys_same
    core_assert_true(sys_check('c', 123, true));
    core_assert_true(sys_equal('c', 123));  # non-strict
    core_assert_true(sys_same('c', 123));   # strict

    # strict/non-strict semantics
    sys_set('num', '10');
    core_assert_true(sys_equal('num', 10));    # '10' == 10
    core_assert_false(sys_same('num', 10));    # '10' !== 10

    # sys_has_value()
    core_assert_true(sys_has_value('A2'));
    core_assert_true(sys_has_value(123));
    core_assert_false(sys_has_value('___no_such_value___'));

    restore_system_registry();
}

/**
 * Registry as plain list:
 * sys_push()/sys_pop(), sys_unshift()/sys_shift(),
 * sys_push_unique()/sys_unshift_unique(),
 * sys_remove()/sys_remove_all(), sys_search(), sys_values().
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

    # push_unique / unshift_unique
    sys_push_unique('a', 'b', 'c');  # 'a' already exists
    core_assert_same_arrays(array('a', 'b', 'c'), sys_values());

    sys_unshift_unique('c', 'd');    # 'c' already exists, 'd' is new
    core_assert_same_arrays(array('d', 'a', 'b', 'c'), sys_values());

    # remove / remove_all
    $key = sys_remove('a');
    core_assert_true($key !== false);
    core_assert_false(sys_has_value('a'));

    sys_push('x', 'x', 'x');
    $deleted = sys_remove_all('x');
    core_assert_same(3, $deleted);
    core_assert_false(sys_has_value('x'));

    # search
    $key_d = sys_search('d');  # existing key
    core_assert_true($key_d !== false);

    $key_x = sys_search('x');  # absent key
    core_assert_false($key_x);

    restore_system_registry();
}

/**
 * Basic sys_opt_* operations and _ensure_opt_array() behavior.
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
    sys_opt_add('colors', 'bg', '#000', false);
    core_assert_same('#fff', sys_opt_get('colors', 'bg'));

    sys_opt_add('colors', 'bg', '#000', true);
    core_assert_same('#000', sys_opt_get('colors', 'bg'));

    # sys_opt_set(): alias to sys_opt_add()
    sys_opt_set('colors', 'fg', '#111');
    core_assert_same('#111', sys_opt_get('colors', 'fg'));

    # has_key / has_value / check / equal / same
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

    # non-existing opt — created as empty array
    core_assert_same(0, sys_opt_count('missing_opt'));
    core_assert_array(sys_get('missing_opt'));

    # _ensure_opt_array() replaces scalar sys-value with array
    sys_set('scalar_opt', 42);
    core_assert_same(42, sys_get('scalar_opt'));

    core_assert_same(0, sys_opt_count('scalar_opt'));  # _ensure_opt_array() is triggered here
    core_assert_array(sys_get('scalar_opt'));

    restore_system_registry();
}

/**
 * Array-like operations for sys_opt_*:
 * push/pop, shift/unshift, push_unique/unshift_unique,
 * insert (including negative indexes and out-of-range),
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

    # push_unique / unshift_unique
    sys_opt_push_unique($opt, 'a', 'b', 'c');  # 'a' already exists
    core_assert_same_arrays(array('a', 'b', 'c'), sys_opt_values($opt));

    sys_opt_unshift_unique($opt, 'c', 'd');    # 'c' already exists, 'd' is new
    core_assert_same_arrays(array('d', 'a', 'b', 'c'), sys_opt_values($opt));

    # insert: position inside range
    sys_opt_insert($opt, 2, 'X');  # d, a, X, b, c
    core_assert_same_arrays(array('d', 'a', 'X', 'b', 'c'), sys_opt_values($opt));

    # insert: negative index (before last element)
    sys_opt_insert($opt, -1, 'Y');  # d, a, X, b, Y, c
    core_assert_same_arrays(array('d', 'a', 'X', 'b', 'Y', 'c'), sys_opt_values($opt));

    # insert: index out of range — append to the end
    $before = sys_opt_count($opt);
    sys_opt_insert($opt, 100, 'Z');
    core_assert_same($before + 1, sys_opt_count($opt));
    core_assert_same('Z', sys_opt_pop($opt));  # must be at the end

    # remove / remove_all
    $deleted_key = sys_opt_remove($opt, 'X');
    core_assert_true($deleted_key !== false);
    core_assert_false(sys_opt_has_value($opt, 'X'));

    sys_opt_push($opt, 'dup', 'dup');
    $deleted = sys_opt_remove_all($opt, 'dup');
    core_assert_same(2, $deleted);
    core_assert_false(sys_opt_has_value($opt, 'dup'));

    # search
    core_assert_same(0, sys_opt_search($opt, 'd'));
    core_assert_false(sys_opt_search($opt, '___no_value___'));

    # clear — clears only this option, but keeps key as array
    sys_opt_clear($opt);
    core_assert_same(0, sys_opt_count($opt));
    core_assert_array(sys_get($opt));

    restore_system_registry();
}

/**
 * P_NULL semantics in sys() and sys_opt() shortcuts.
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
