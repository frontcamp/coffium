<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/*
System registry API ($SYS)

P_NULL - pseudo NULL value
is_pnull(v) - check if v identical (===) to P_NULL

restore_system_registry()
set_system_registry($new_sys=array(), $auto_store=true)
store_system_registry()
sys_hash() - calculate MD5 hash of serialized $SYS

Top level API
=============
sys(k,v) - get/set shortcut
sys_add(k,v) - add given key/val pair to the end of an array [+overwrite]
sys_check(k,v) - check key exists and its value equal [+strict]
sys_clear() - clear an array
sys_count() - return elements count
sys_delete(k) - delete pointed key
sys_equal(k,v) - alias to sys_check() with turned off strict flag
sys_same(k,v) - alias to sys_check() with turned on strict flag
sys_get(k) - return value by key, or P_NULL if does not exist
sys_has_key(k) - checks if a key exists in an array
sys_has_value(v) - checks if a value exists in an array [+strict]
sys_keys() - return a list of all keys
sys_values() - return a list of all values
sys_pop() - pop the element off the end of array
sys_shift() - extract first value of array
sys_push(v) - push one or more elements onto the end of array
sys_unshift(v) - add value to the beginning of an array
sys_push_unique(v) - push one or more unique elements onto the end of array
sys_unshift_unique(v) - add unique value to the beginning of an array
sys_remove(v) - remove 1st occurrence of a given value [+strict]
sys_remove_all(v) - remove all occurrences of a given value [+strict]
sys_search(v) - find first instance of v and return its key, false otherwise
sys_set(k,v) - alias to sys_add()

Sub array API (aka "options")
=============================
sys_opt(o,k,v) - get/set shortcut
sys_opt_init(o) - ensure options branch exists (create if needed)
sys_opt_exists(o) - check if options array exists
sys_opt_add(o,k,v) - add given key/val pair to the end of an array [+overwrite]
sys_opt_set(o,k,v) - alias to sys_opt_add()
sys_opt_check(o,k,v) - check key exists and its value equal [+strict]
sys_opt_clear(o) - clear an array
sys_opt_count(o) - return elements count
sys_opt_delete(o,k) - delete pointed key
sys_opt_equal(o,k,v) - alias to sys_opt_check() with strict flag disabled
sys_opt_same(o,k,v) - alias to sys_opt_check() with strict flag enabled
sys_opt_get(o,k) - return value by key, or P_NULL if does not exist
sys_opt_has_key(o,k) - checks if a key exists in an array
sys_opt_has_value(o,v) - checks if a value exists in an array [+strict]
sys_opt_insert(o,p,v) - insert value at given position
sys_opt_keys(o) - return a list of all keys
sys_opt_values(o) - return a list of all values
sys_opt_pop(o) - pop the element off the end of array
sys_opt_shift(o) - extract first value of array
sys_opt_push(o,v) - push one or more elements onto the end of an array
sys_opt_unshift(o,v) - add value to the beginning of an array
sys_opt_push_unique(o,v) - push one or more unique elements onto the end of an array
sys_opt_unshift_unique(o,v) - add unique value to the beginning of an array
sys_opt_remove(o,v) - remove 1st occurrence of a given value [+strict]
sys_opt_remove_all(o,v) - remove all occurrences of a given value [+strict]
sys_opt_search(o,v) - find first instance of v and return its key, false otherwise
*/

global $SYS;

if (!defined('P_NULL'))
{
    define('P_NULL', 'P$#_D0');  # Pseudo NULL
    function is_pnull(mixed $value): bool { return $value === P_NULL; }
}


/**
 * ERROR HANDLING
 * --------------
 */

class SysRegException extends \RuntimeException {}

define('E_STACK_CORRUPT', 'Registry stack absent or corrupted!');
define('E_STACK_INVALID', 'System registry restored value must be an array.');
define('E_REG_CORRUPT', 'System registry is absent or corrupted.');
define('E_OPT_CORRUPT', 'Options registry entry is absent or corrupted.');

function _sys_stack_check(): bool
{
    global $_SYS_STACK;
    return isset($_SYS_STACK) && is_array($_SYS_STACK);
}

function _sys_stack_state() {
    global $_SYS_STACK;
    return ' Current state: '
         . (isset($_SYS_STACK) ? gettype($_SYS_STACK) : 'undefined.');
}

function _sys_reg_check(): bool
{
    global $SYS;
    return isset($SYS) && is_array($SYS);
}

function _sys_reg_state() {
    global $SYS;
    return ' Current state: '
         . (isset($SYS) ? gettype($SYS) : 'undefined.');
}

function _sys_opt_check(int|string $opt): bool
{
    global $SYS;

    return array_key_exists($opt, $SYS) && is_array($SYS[$opt]);
}

function _sys_opt_state(int|string $opt): string
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    if (!array_key_exists($opt, $SYS)) {
        return ' Missing option: "'.$opt.'".';
    }

    if (!is_array($SYS[$opt])) {
        return ' Option "'.$opt.'" has type "'.gettype($SYS[$opt]).'".';
    }

    return ' Option "'.$opt.'" contains '.count($SYS[$opt]).' elements.';
}

/**
 * REGISTRY STACK
 * --------------
 */

$_SYS_STACK = array();

function restore_system_registry(): bool
{
    global $SYS, $_SYS_STACK;

    if (!_sys_stack_check()) throw new SysRegException(E_STACK_CORRUPT._sys_stack_state());

    if (empty($_SYS_STACK)) {
        return false;
    }

    $restored = array_pop($_SYS_STACK);

    if (!is_array($restored)) throw new SysRegException(E_STACK_INVALID._sys_stack_state());

    $SYS = $restored;
    return true;
}

function set_system_registry(
            array $new_sys = array(),
            bool $auto_store = true
         ): array
{
    global $SYS, $_SYS_STACK;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_stack_check()) throw new SysRegException(E_STACK_CORRUPT._sys_stack_state());

    if ($auto_store) array_push($_SYS_STACK, $SYS);  # store current

    $SYS = $new_sys;  # set new registry

    return $SYS;
}

function store_system_registry(): array
{
    global $SYS, $_SYS_STACK;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_stack_check()) throw new SysRegException(E_STACK_CORRUPT._sys_stack_state());

    array_push($_SYS_STACK, $SYS);  # store current
    return $SYS;
}

function sys_hash(): string
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return md5(serialize($SYS));
}


/**
 * TOP (1ST) LEVEL API
 * -------------------
 */

function sys(int|string $key, mixed $val = P_NULL): mixed
{
    if (is_pnull($val)) {      # get
        return sys_get($key);
    } else {                   # set
        sys_add($key, $val);
        return P_NULL;
    }
}

function sys_add(
            int|string $key,
            mixed $val,
            bool $overwrite = true
         ): void
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    if ($overwrite || !array_key_exists($key, $SYS)) {
        $SYS[$key] = $val;
    }
}

function sys_check(
            int|string $key,
            mixed $val,
            bool $strict = true
         ): bool
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_key_exists($key, $SYS)
       && (($strict === false && $SYS[$key] == $val)
        || ($strict === true && $SYS[$key] === $val));
}

function sys_clear(): void
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    $SYS = array();
}

function sys_count(): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return count($SYS);
}

function sys_delete(int|string $key): void
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    unset($SYS[$key]);
}

function sys_equal(int|string $key, mixed $val): bool
{
    return sys_check($key, $val, false);
}

function sys_same(int|string $key, mixed $val): bool
{
    return sys_check($key, $val, true);
}

function sys_get(int|string $key): mixed
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_key_exists($key, $SYS) ? $SYS[$key] : P_NULL;
}


function sys_has_key(int|string $key): bool
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_key_exists($key, $SYS);
}

function sys_has_value(mixed $value, bool $strict = true): bool
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return in_array($value, $SYS, $strict);
}

function sys_keys(): array
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_keys($SYS);
}

function sys_values(): array
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_values($SYS);
}

function sys_pop(): mixed
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_pop($SYS);
}

function sys_shift(): mixed
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_shift($SYS);
}

function sys_push(mixed ...$vals): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_push($SYS, ...$vals);
}

function sys_unshift(mixed ...$vals): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_unshift($SYS, ...$vals);
}

function sys_push_unique(mixed ...$vals): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    foreach ($vals as $val) {
        if (!sys_has_value($val)) {
            $SYS[] = $val;
        }
    }

    return count($SYS);
}

function sys_unshift_unique(mixed ...$vals): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    foreach (array_reverse($vals) as $val) {
        if (!sys_has_value($val)) {
            array_unshift($SYS, $val);
        }
    }

    return count($SYS);
}

function sys_remove(mixed $val, bool $strict = true): int|string|false
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    $key = array_search($val, $SYS, $strict);

    if ($key === false) {
        return false;
    }

    unset($SYS[$key]);
    return $key;  # return deleted key on success
}

function sys_remove_all(mixed $val, bool $strict = true): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    $deleted = 0;

    foreach ($SYS as $key => $item) {
        if ($strict ? $item === $val : $item == $val) {
            unset($SYS[$key]);
            $deleted++;
        }
    }

    return $deleted;
}

function sys_search(mixed $val, bool $strict = true): int|string|false
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_search($val, $SYS, $strict);
}

function sys_set(int|string $key, mixed $val): void
{
    sys_add($key, $val);
}


/**
 * SUB (2ND) LEVEL API AKA "OPTIONS" (opt, opts)
 * ---------------------------------------------
 */

function sys_opt(
            int|string $opt,
            int|string $key,
            mixed $val = P_NULL
         ): mixed
{
    if (is_pnull($val)) {  # get
        return sys_opt_get($opt, $key);
    } else {               # set
        sys_opt_add($opt, $key, $val);
        return P_NULL;
    }
}

function sys_opt_init(
            int|string $opt,
            bool $overwrite = true
         ): bool
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    if (array_key_exists($opt, $SYS))  # options branch exists
    {
        if (is_array($SYS[$opt]))  # options branch is array
        {
            return true;  # already exists and array
        }
        else  # options branch is not array
        {
            if ($overwrite) {  # if overwrite allowed
                $SYS[$opt] = array();  # convert to array
                return true;  # success
            } else {  # overwrite is not allowed
                throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));
            }
        }
    }
    else  # options branch is absent
    {
        $SYS[$opt] = array();  # create branch
        return true;  # success
    }
}

function sys_opt_exists(int|string $opt): bool
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    return array_key_exists($opt, $SYS) && is_array($SYS[$opt]);
}

function sys_opt_add(
            int|string $opt,
            int|string $key,
            mixed $val,
            bool $overwrite = true
         ): bool
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    if (array_key_exists($opt, $SYS))  # options branch exists
    {
        if (is_array($SYS[$opt]))  # options branch is array
        {
            if (array_key_exists($key, $SYS[$opt]))  # key already exists
            {
                if ($overwrite) {  # overwrite allowed
                    $SYS[$opt][$key] = $val;
                    return true;  # success
                } else {  # overwrite is not allowed
                    return false;  # fail
                }
            }
            else  # key is absent
            {
                $SYS[$opt][$key] = $val;
                return true;  # success
            }
        }
        else  # options branch is not array
        {
            if ($overwrite) {  # if overwrite allowed
                $SYS[$opt] = array();  # convert to array
                $SYS[$opt][$key] = $val;  # add key
                return true;  # success
            } else {  # overwrite is not allowed
                throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));
            }
        }
    }
    else  # option branch is absent
    {
        $SYS[$opt][$key] = $val;  # create branch and key with value
        return true;  # success
    }
}

function sys_opt_set(
            int|string $opt,
            int|string $key,
            mixed $val
         ): bool
{
    return sys_opt_add($opt, $key, $val);
}

function sys_opt_check(
            int|string $opt,
            int|string $key,
            mixed $val,
            bool $strict = true
         ): bool
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return array_key_exists($key, $SYS[$opt])
       && (($strict === false && $SYS[$opt][$key] == $val)
        || ($strict === true && $SYS[$opt][$key] === $val));
}

function sys_opt_clear(int|string $opt): void
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    $SYS[$opt] = array();
}

function sys_opt_count(int|string $opt): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return count($SYS[$opt]);
}

function sys_opt_delete(int|string $opt, int|string $key): void
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    unset($SYS[$opt][$key]);
}

function sys_opt_equal(
            int|string $opt,
            int|string $key,
            mixed $val
         ): bool
{
    return sys_opt_check($opt, $key, $val, false);
}

function sys_opt_same(
            int|string $opt,
            int|string $key,
            mixed $val
         ): bool
{
    return sys_opt_check($opt, $key, $val, true);
}

function sys_opt_get(int|string $opt, int|string $key): mixed
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return array_key_exists($key, $SYS[$opt]) ? $SYS[$opt][$key] : P_NULL;
}

function sys_opt_has_key(int|string $opt, int|string $key): bool
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return array_key_exists($key, $SYS[$opt]);
}

function sys_opt_has_value(
            int|string $opt,
            mixed $value,
            bool $strict = true
         ): bool
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return in_array($value, $SYS[$opt], $strict);
}

function sys_opt_insert(int|string $opt, int $pos, mixed $val): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    $len = count($SYS[$opt]);

    # negative index: position from the end (like Python)
    if ($pos < 0) {
        $pos = $len + $pos;      # -1 => $len-1 (before last element)
        if ($pos < 0) $pos = 0;  # clamp to start if still negative
    }

    if ($pos < $len) {
        array_splice($SYS[$opt], $pos, 0, $val);
    } else {
        // index >= length => append to the end
        sys_opt_push($opt, $val);
    }

    return count($SYS[$opt]);
}

function sys_opt_keys(int|string $opt): array
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return array_keys($SYS[$opt]);
}

function sys_opt_values(int|string $opt): array
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return array_values($SYS[$opt]);
}

function sys_opt_pop(int|string $opt): mixed
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return array_pop($SYS[$opt]);
}

function sys_opt_shift(int|string $opt): mixed
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return array_shift($SYS[$opt]);
}

function sys_opt_push(int|string $opt, mixed ...$vals): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    if (!array_key_exists($opt, $SYS)) {  # options branch absent
        $SYS[$opt] = array();  # create options branch
    } elseif (!is_array($SYS[$opt])) {  # .. exists, but not array
        throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));
    }

    # options branch exists and array
    return array_push($SYS[$opt], ...$vals);
}

function sys_opt_unshift(int|string $opt, mixed ...$vals): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    if (!array_key_exists($opt, $SYS)) {  # options branch absent
        $SYS[$opt] = array();  # create options branch
    } elseif (!is_array($SYS[$opt])) {  # .. exists, but not array
        throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));
    }

    return array_unshift($SYS[$opt], ...$vals);
}

function sys_opt_push_unique(int|string $opt, mixed ...$vals): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    if (!array_key_exists($opt, $SYS)) {  # options branch absent
        $SYS[$opt] = array();  # create options branch
    } elseif (!is_array($SYS[$opt])) {  # .. exists, but not array
        throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));
    }

    foreach ($vals as $val) {
        if (!in_array($val, $SYS[$opt], true)) {
            array_push($SYS[$opt], $val);
        }
    }
    return count($SYS[$opt]);
}

function sys_opt_unshift_unique(int|string $opt, mixed ...$vals): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());

    if (!array_key_exists($opt, $SYS)) {  # options branch absent
        $SYS[$opt] = array();  # create options branch
    } elseif (!is_array($SYS[$opt])) {  # .. exists, but not array
        throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));
    }

    foreach (array_reverse($vals) as $val) {
        if (!in_array($val, $SYS[$opt], true)) {
            array_unshift($SYS[$opt], $val);
        }
    }
    return count($SYS[$opt]);
}

function sys_opt_remove(
            int|string $opt,
            mixed $val,
            bool $strict = true
         ): int|string|false
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    $key = array_search($val, $SYS[$opt], $strict);
    if (false !== $key) {
        unset($SYS[$opt][$key]);
        return $key;  # return deleted key on success
    }
    return false;  # fail
}

function sys_opt_remove_all(
            int|string $opt,
            mixed $val,
            bool $strict = true
         ): int
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    $deleted = 0;

    foreach ($SYS[$opt] as $key => $item) {
        if ($strict ? $item === $val : $item == $val) {
            unset($SYS[$opt][$key]);
            $deleted++;
        }
    }

    return $deleted;
}

function sys_opt_search(
            int|string $opt,
            mixed $val,
            bool $strict = true
         ): int|string|false
{
    global $SYS;

    if (!_sys_reg_check()) throw new SysRegException(E_REG_CORRUPT._sys_reg_state());
    if (!_sys_opt_check($opt)) throw new SysRegException(E_OPT_CORRUPT._sys_opt_state($opt));

    return array_search($val, $SYS[$opt], $strict);
}

