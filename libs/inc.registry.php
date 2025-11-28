<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/*
System registry API ($SYS)

P_NULL - pseudo NULL value
is_pnull(v) - check if v identical (===) to P_NULL

restore_system_registry()
set_system_registry($new_sys=NULL, $auto_store=true)
store_system_registry()
sys_hash() - calculate MD5 hash of serialized $SYS

Top level API
=============
sys(k,v) - get/set shortcut
sys_add(k,v) - add given key/val pair to the end of an array [+overwrite]
sys_check(k,v) - check key exists and its value equal [-strict]
sys_clear() - clear an array
sys_count() - return elements count
sys_delete(k) - delete pointed key
sys_equal(k,v) - alias to sys_check() with turned off strict flag
sys_get(k) - return value by key, or P_NULL if does not exist
sys_has_key(k) - checks if a key exists in an array
sys_has_value(v) - checks if a value exists in an array
sys_keys() - return a list of all keys
sys_values() - return a list of all values
sys_pop() - pop the element off the end of array
sys_shift() - extract first value of array
sys_push(v) - push one or more elements onto the end of array
sys_unshift(v) - add value to the beginning of an array
sys_push_unique(v) - push one or more unique elements onto the end of array
sys_unshift_unique(v) - add unique value to the beginning of an array
sys_remove(v) - remove 1st occurrence of a given value [-strict]
sys_remove_all(v) - remove all occurrences of a given value [-strict]
sys_same(k,v) - alias to sys_check() with turned on strict flag
sys_search(v) - find first instance of v and return its key, null otherwise
sys_set(k,v) - alias to sys_add()

Sub array API (aka "options")
=============================
sys_opt(o,k,v) - get/set shortcut
sys_opt_add(o,k,v) - add given key/val pair to the end of an array [+overwrite]
sys_opt_check(o,k,v) - check key exists and its value equal [-strict]
sys_opt_clear(o) - clear an array
sys_opt_count(o) - return elements count
sys_opt_delete(o,k) - delete pointed key
sys_opt_equal(o,k,v) - alias to sys_opt_check() with strict flag disabled
sys_opt_get(o,k) - return value by key, or P_NULL if does not exist
sys_opt_has_key(o,k) - checks if a key exists in an array
sys_opt_has_value(o,v) - checks if a value exists in an array
sys_opt_insert(o,p,v) - insert value at given position
sys_opt_keys(o) - return a list of all keys
sys_opt_values(o) - return a list of all values
sys_opt_pop(o) - pop the element off the end of array
sys_opt_shift(o) - extract first value of array
sys_opt_push(o,v) - push one or more elements onto the end of an array
sys_opt_unshift(o,v) - add value to the beginning of an array
sys_opt_push_unique(o,v) - push one or more unique elements onto the end of an array
sys_opt_unshift_unique(o,v) - add unique value to the beginning of an array
sys_opt_remove(o,v) - remove 1st occurrence of a given value [-strict]
sys_opt_remove_all(o,v) - remove all occurrences of a given value [-strict]
sys_opt_same(o,k,v) - alias to sys_opt_check() with strict flag enabled
sys_opt_search(o,v) - find first instance of v and return its key, null otherwise
sys_opt_set(o,k,v) - alias to sys_opt_add()
*/

global $SYS;

if (!defined('P_NULL'))
{
    define('P_NULL', 'P$#_D0');  # Pseudo NULL
    function is_pnull(mixed $value): bool { return $value === P_NULL; }
}

/**
 * Common
 */

$_SYS_STACK = array();

function restore_system_registry(): bool
{
    global $SYS, $_SYS_STACK;
    if (empty($_SYS_STACK)) return false;
    $SYS = array_pop($_SYS_STACK);
    return true;
}

function set_system_registry(
            ?array $new_sys = null,
            bool $auto_store = true
         ): array
{
    global $SYS, $_SYS_STACK;
    if ($auto_store) array_push($_SYS_STACK, $SYS);
    if (!is_null($new_sys)) $SYS = $new_sys;  # set new
    return $SYS;
}

function store_system_registry(): array
{
    global $SYS, $_SYS_STACK;
    array_push($_SYS_STACK, $SYS);  # store current
    return $SYS;
}

function sys_hash(): string
{
    global $SYS;
    return md5(serialize($SYS));
}

/**
 * Top (1st) level API
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
    if (!array_key_exists($key, $SYS) || $overwrite) $SYS[$key] = $val;
}

function sys_check(
            int|string $key,
            mixed $val,
            bool $strict = false
         ): bool
{
    global $SYS;
    return array_key_exists($key, $SYS)
       && (($strict === false && $SYS[$key] == $val)
        || ($strict === true && $SYS[$key] === $val));
}

function sys_clear(): void
{
    $GLOBALS['SYS'] = array();
}

function sys_count(): int
{
    return count($GLOBALS['SYS']);
}

function sys_delete(int|string $key): void
{
    unset($GLOBALS['SYS'][$key]);
}

function sys_equal(int|string $key, mixed $val): bool
{
    return sys_check($key, $val, false);
}

function sys_get(int|string $key): mixed
{
    global $SYS;
    return array_key_exists($key, $SYS) ? $SYS[$key] : P_NULL;
}

function sys_has_key(int|string $key): bool
{
    return array_key_exists($key, $GLOBALS['SYS']);
}

function sys_has_value(mixed $value, bool $strict = false): bool
{
    return in_array($value, $GLOBALS['SYS'], $strict);
}

function sys_keys(): array { return array_keys($GLOBALS['SYS']); }

function sys_values(): array { return array_values($GLOBALS['SYS']); }

function sys_pop(): mixed { return array_pop($GLOBALS['SYS']); }

function sys_shift(): mixed { return array_shift($GLOBALS['SYS']); }

function sys_push(mixed ...$vals): int { return array_push($GLOBALS['SYS'], ...$vals); }

function sys_unshift(mixed ...$vals): int { return array_unshift($GLOBALS['SYS'], ...$vals); }

function sys_push_unique(mixed ...$vals): int
{
    foreach ($vals as $val) {
        if (!sys_has_value($val)) array_push($GLOBALS['SYS'], $val);
    }
    return count($GLOBALS['SYS']);
}

function sys_unshift_unique(mixed ...$vals): int
{
    foreach (array_reverse($vals) as $val) {
        if (!sys_has_value($val)) array_unshift($GLOBALS['SYS'], $val);
    }
    return count($GLOBALS['SYS']);
}

function sys_remove(mixed $val, bool $strict = false): int|string|false
{
    global $SYS;
    if (false !== ($key = array_search($val, $SYS, $strict))) {
        unset($SYS[$key]);
        return $key;  # return deleted key on success
    }
    return false;  # fail
}

function sys_remove_all(mixed $val, bool $strict = false): int
{
    global $SYS;
    $deleted = 0;
    foreach ($SYS as $key => $item) {
        if ($strict ? $item === $val : $item == $val) {
            unset($SYS[$key]);
            $deleted++;
        }
    }
    return $deleted;
}

function sys_same(int|string $key, mixed $val): bool
{
    return sys_check($key, $val, true);
}

function sys_search(mixed $val, bool $strict = false): int|string|false
{
    return array_search($val, $GLOBALS['SYS'], $strict);
}

function sys_set(int|string $key, mixed $val): void
{
    sys_add($key, $val);
}

/**
 * Sub (2nd) level API aka "options" (opt, opts)
 */

function _ensure_opt_array(int|string $opt): void
{
    global $SYS;
    if (!array_key_exists($opt, $SYS) || !is_array($SYS[$opt])) {
        $SYS[$opt] = array();
    }
}

function sys_opt(
            int|string $opt,
            int|string $key,
            mixed $val = P_NULL
         ): mixed
{
    if (is_pnull($val)) {
        return sys_opt_get($opt, $key);
    } else {
        sys_opt_add($opt, $key, $val);
        return P_NULL;
    }
}

function sys_opt_add(
            int|string $opt,
            int|string $key,
            mixed $val,
            bool $overwrite = true
         ): void
{
    global $SYS;
    _ensure_opt_array($opt);
    if (!array_key_exists($key, $SYS[$opt]) || $overwrite) $SYS[$opt][$key] = $val;
}

function sys_opt_check(
            int|string $opt,
            int|string $key,
            mixed $val,
            bool $strict=false
         ): bool
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_key_exists($key, $SYS[$opt])
       && (($strict === false && $SYS[$opt][$key] == $val)
        || ($strict === true && $SYS[$opt][$key] === $val));
}

function sys_opt_clear(int|string $opt): void
{
    global $SYS;
    _ensure_opt_array($opt);
    $SYS[$opt] = array();
}

function sys_opt_count(int|string $opt): int
{
    global $SYS;
    _ensure_opt_array($opt);
    return count($SYS[$opt]);
}

function sys_opt_delete(int|string $opt, int|string $key): void
{
    global $SYS;
    _ensure_opt_array($opt);
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

function sys_opt_get(int|string $opt, int|string $key): mixed
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_key_exists($key, $SYS[$opt]) ? $SYS[$opt][$key] : P_NULL;
}

function sys_opt_has_key(int|string $opt, int|string $key): bool
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_key_exists($key, $SYS[$opt]);
}

function sys_opt_has_value(
            int|string $opt,
            mixed $value,
            bool $strict=false
         ): bool
{
    global $SYS;
    _ensure_opt_array($opt);
    return in_array($value, $SYS[$opt], $strict);
}

function sys_opt_insert(int|string $opt, int $pos, mixed $val): void
{
    global $SYS;
    _ensure_opt_array($opt);

    if ($pos < 0) $pos = 0;

    if ($pos < count($SYS[$opt])) {
        array_splice($SYS[$opt], $pos, 0, $val);
    } else {
        sys_opt_push($opt, $val);
    }
}

function sys_opt_keys(int|string $opt): array
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_keys($SYS[$opt]);
}

function sys_opt_values(int|string $opt): array
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_values($SYS[$opt]);
}

function sys_opt_pop(int|string $opt): mixed
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_pop($SYS[$opt]);
}

function sys_opt_shift(int|string $opt): mixed
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_shift($SYS[$opt]);
}

function sys_opt_push(int|string $opt, mixed ...$vals): int
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_push($SYS[$opt], ...$vals);
}

function sys_opt_unshift(int|string $opt, mixed ...$vals): int
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_unshift($SYS[$opt], ...$vals);
}

function sys_opt_push_unique(int|string $opt, mixed ...$vals): int
{
    global $SYS;
    _ensure_opt_array($opt);
    foreach ($vals as $val) {
        if (!sys_opt_has_value($opt, $val)) {
            array_push($SYS[$opt], $val);
        }
    }
    return count($SYS[$opt]);
}

function sys_opt_unshift_unique(int|string $opt, mixed ...$vals): int
{
    global $SYS;
    _ensure_opt_array($opt);
    foreach (array_reverse($vals) as $val) {
        if (!sys_opt_has_value($opt, $val)) {
            array_unshift($SYS[$opt], $val);
        }
    }
    return count($SYS[$opt]);
}

function sys_opt_remove(
            int|string $opt,
            mixed $val,
            bool $strict=false
         ): int|string|false
{
    global $SYS;
    _ensure_opt_array($opt);
    if (false !== ($key = array_search($val, $SYS[$opt], $strict))) {
        unset($SYS[$opt][$key]);
        return $key;  # return deleted key on success
    }
    return false;  # fail
}

function sys_opt_remove_all(
            int|string $opt,
            mixed $val,
            bool $strict=false
         ): int
{
    global $SYS;
    _ensure_opt_array($opt);
    $deleted = 0;

    foreach ($SYS[$opt] as $key => $item) {
        if ($strict ? $item === $val : $item == $val) {
            unset($SYS[$opt][$key]);
            $deleted++;
        }
    }

    return $deleted;
}

function sys_opt_same(
            int|string $opt,
            int|string $key,
            mixed $val
         ): bool
{
    return sys_opt_check($opt, $key, $val, true);
}

function sys_opt_search(
            int|string $opt,
            mixed $val,
            bool $strict=false
         ): int|string|false
{
    global $SYS;
    _ensure_opt_array($opt);
    return array_search($val, $SYS[$opt], $strict);
}

function sys_opt_set(
            int|string $opt,
            int|string $key,
            mixed $val
         ): void
{
    sys_opt_add($opt, $key, $val);
}

