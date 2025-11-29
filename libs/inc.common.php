<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Common functionality */


/**
 * CONVERTING BOOL TYPES
 * ---------------------
 */

/**
 * Convert a boolean value to integer representation (1 or 0).
 *
 * @param bool $value Source boolean value.
 * @return int 1 for true, 0 for false.
 */
function bool_to_int(bool $value): int
{
    return $value ? 1 : 0;
}

/**
 * Convert an integer flag (>=1) to boolean.
 *
 * @param int $value Integer flag.
 * @return bool True if value is greater or equal to 1.
 */
function int_to_bool(int $value): bool
{
    return $value >= 1;
}

/**
 * Convert a strict boolean to "On"/"Off".
 *
 * @param bool $value Source boolean value.
 * @return string "On" for true, "Off" for false.
 */
function bool_to_onoff(bool $value): string
{
    return $value ? 'On' : 'Off';
}

/**
 * Convert a strict boolean to "Yes"/"No".
 *
 * @param bool $value Source boolean value.
 * @return string "Yes" for true, "No" for false.
 */
function bool_to_yesno(bool $value): string
{
    return $value ? 'Yes' : 'No';
}

/**
 * Convert a loosely-typed value to "On"/"Off" using PHP loose comparison rules.
 *
 * @param mixed $value Any value that can be treated as boolean-like.
 * @return string "On" if $value == true, "Off" otherwise.
 */
function some_to_onoff(mixed $value): string
{
    return ($value == true) ? 'On' : 'Off';
}

/**
 * Convert a loosely-typed value to "Yes"/"No" using PHP loose comparison rules.
 *
 * @param mixed $value Any value that can be treated as boolean-like.
 * @return string "Yes" if $value == true, "No" otherwise.
 */
function some_to_yesno(mixed $value): string
{
    return ($value == true) ? 'Yes' : 'No';
}


/**
 * CHECKING NUMERIC TYPES
 * ----------------------
 */

/**
 * Check whether a value is a float or an integer.
 *
 * @param mixed $value Value to check.
 * @return bool True for float or int, false otherwise.
 */
function is_float_or_int(mixed $value): bool
{
    return is_float($value) || is_int($value);
}

/**
 * Check whether a value is an integer or a float.
 *
 * Alias of is_float_or_int() with reversed wording.
 *
 * @param mixed $value Value to check.
 * @return bool True for int or float, false otherwise.
 */
function is_int_or_float(mixed $value): bool
{
    return is_int($value) || is_float($value);
}

/**
 * Check whether a value is an integer or a sign-prefixed integer string.
 *
 * Examples of values returning true: 123, "123", "-5", "+42"
 *
 * @param int|string $value Integer or string to inspect.
 * @return bool True if $value represents an integer, false otherwise.
 */
function is_int_like(int|string $value): bool
{
    if (is_int($value)) return true;

    $str = (string) $value;

    $has_sign = str_starts_with($str, '-')
             || str_starts_with($str, '+');

    return $has_sign
        ? ctype_digit(substr($str, 1))
        : ctype_digit($str);
}

/**
 * Check if a string contains a decimal point.
 *
 * @param string $value String representation of a number.
 * @return bool True if the string contains a dot, false otherwise.
 */
function is_decimal(string $value): bool
{
    return (false !== strpos($value, '.'));
}


/**
 * NUMERICAL UTILITIES
 * -------------------
 */

/**
 * Clamp numeric value to the given [min, max] range.
 *
 * @param int|float $value Value to clamp.
 * @param int|float $min   Lower bound.
 * @param int|float $max   Upper bound.
 * @return int|float Clamped value.
 */
function ntrim(
            int|float $value,
            int|float $min,
            int|float $max
         ): int|float
{
    if ($value < $min) $value = $min;
    if ($value > $max) $value = $max;
    return $value;
}


/**
 * COLLECTIONS / ARRAYS
 * --------------------
 */

/**
 * Return a new empty array by reference.
 *
 * Useful for passing to APIs that expect parameters by reference (array &...).
 *
 * @return array Empty array (returned by reference).
 */
function &empty_array(): array
{
    $a = array();
    return $a;
}


/**
 * WORKING WITH DATA FILES
 * -----------------------
 */

/**
 * Load a variable from a PHP data file.
 *
 * The included file is expected to define a variable with the given name.
 *
 * @param string $file_path Absolute or relative path to the data file.
 * @param string $var_name  Variable name to read from the file.
 * @return mixed Value of the requested variable.
 */
function file_get_data(
            string $file_path,
            string $var_name = 'DATA'
         ): mixed
{
    if (!is_file($file_path))
    {
        trigger_error('Data file does not exist: '.$file_path, E_USER_ERROR);
        die();
    }

    include $file_path;

    if (!isset($$var_name))
    {
        trigger_error('Data variable is absent: '.$var_name, E_USER_ERROR);
        die();
    }

    return $$var_name;
}

/**
 * Store a variable into a PHP data file.
 *
 * The file will contain a PHP variable assignment that can be loaded via file_get_data().
 *
 * @param string $file_path Absolute or relative path to the target file.
 * @param mixed  $data      Data to export.
 * @param string $var_name  Variable name to use inside the file.
 * @return void
 */
function file_put_data(
            string $file_path,
            mixed $data,
            string $var_name = 'DATA'
         ): void
{
    $s = "<?php\n\n$".$var_name.' = '.var_export($data, true).";\n\n";
    file_put_contents($file_path, $s, LOCK_EX);
}


/**
 * FYLE SYSTEM & PATHS
 * -------------------
 */

/**
 * Path normalization similar to Python's os.path.normpath,
 * but WITHOUT processing "." and ".." segments.
 *
 * - Unifies slashes and backslashes to the current DIRECTORY_SEPARATOR.
 * - Collapses duplicate separators.
 * - Preserves absolute / UNC / drive-prefixed paths.
 * - Does NOT resolve "." or "..".
 */
function path_normalize(string $path): string
{
    $path = trim($path);

    if ($path === '') {
        // Python's normpath('') returns ".", but here we keep it empty.
        return '';
    }

    // Normalize all separators to "/" for internal processing
    $path = str_replace(array('\\', '/'), '/', $path);

    $prefix   = '';
    $absolute = false;
    $unc      = false;

    // Windows drive prefix: "C:..."
    if (preg_match('~^[A-Za-z]:~', $path)) {
        $prefix = substr($path, 0, 2);
        $path   = substr($path, 2);
    }

    // UNC path ("//server/share") or regular absolute path ("/foo/bar")
    if (strncmp($path, '//', 2) === 0) {
        $unc  = true;
        $path = substr($path, 2);
    } elseif (strncmp($path, '/', 1) === 0) {
        $absolute = true;
        $path     = substr($path, 1);
    }

    $segments = explode('/', $path);
    $parts    = array();

    foreach ($segments as $seg) {
        # collapse empty segments (duplicate slashes)
        if ($seg === '') continue;

        # DO NOT process "." or ".." specially here:
        # they are kept as-is, unlike Python normpath.
        $parts[] = $seg;
    }

    $path = implode('/', $parts);

    if ($absolute) $path = '/'.$path;
    if ($unc) $path = '//'.$path;
    if ($prefix !== '') $path = $prefix.$path;
    if ($path === '' && $absolute) {
        $path = '/';
    } elseif ($path === '' && $prefix !== '') {
        $path = $prefix . '/';
    }

    # convert internal "/" back to OS-specific separator
    if (DIRECTORY_SEPARATOR !== '/') {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    return $path;
}

/**
 * Simple joining of path parts without normalization.
 *
 * - Skips empty parts.
 * - If a part looks like an absolute path, it replaces the previous result.
 * - Uses DIRECTORY_SEPARATOR between non-empty parts.
 */
function path_join(string ...$parts): string
{
    $result = '';

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }

        if ($result === '') {
            $result = $part;
            continue;
        }

        # if the next part is an absolute path or UNC/drive-prefixed path,
        # drop everything before it (Python-like os.path.join behavior).
        if (
            $part[0] === '/' ||
            $part[0] === '\\' ||
            preg_match('~^[A-Za-z]:[\\\\/]~', $part) ||
            strncmp($part, '//', 2) === 0
        ) {
            $result = $part;
            continue;
        }

        $result = rtrim($result, "/\\") . DIRECTORY_SEPARATOR . ltrim($part, "/\\");
    }

    return $result;
}

/**
 * Join multiple path parts and normalize the result.
 */
function path_normjoin(string ...$parts): string
{
    if (!$parts) return '';
    return path_normalize(path_join(...$parts));
}

/**
 * Convert absolute filesystem path to a project-relative path.
 *
 * Both $path and $root may use "/" or "\" separators; they are normalized internally.
 * If $path is not located inside $root, an InvalidArgumentException is thrown.
 *
 * @param string $path Absolute path to convert.
 * @param string $root Project root (default: PROJ_ROOT).
 * @return string Relative path with normalized directory separators.
 *
 * @throws InvalidArgumentException When $path is not within $root.
 */
function abs_to_rel(string $path, string $root=PROJ_ROOT): string
{
    $ds = DIRECTORY_SEPARATOR;

    $normalize = static function (string $p) use ($ds): string {
        # normalize all separators to the current OS
        $p = str_replace(['/', '\\'], $ds, $p);

        # collapse duplicate separators (e.g. "//" -> "/")
        $p = preg_replace('~' . preg_quote($ds, '~') . '+~', $ds, $p);

        # on Windows, keep pure drive roots like "C:\"
        if ($ds === '\\') {
            if (preg_match('~^[A-Za-z]:\\\\$~', $p)) {
                return strtoupper($p);
            }
        }

        # trim trailing separator except for root ("/" or "C:\")
        if (strlen($p) > 1) {
            $p = rtrim($p, $ds);
        }

        return $p;
    };

    $path_norm = $normalize($path);
    $root_norm = $normalize($root);

    # if path is exactly the project root, return a single separator
    if ($path_norm === $root_norm) {
        return $ds;
    }

    # special case: project root is filesystem root ("/" on Unix)
    if ($root_norm === $ds) {
        $rel = ltrim($path_norm, $ds);
        return $rel === '' ? $ds : $rel;
    }

    $isWindows = ($ds === '\\');

    # case-insensitive comparison on Windows, case-sensitive on Unix
    $path_cmp = $isWindows ? strtolower($path_norm) : $path_norm;
    $root_cmp = $isWindows ? strtolower($root_norm) : $root_norm;

    $root_len = strlen($root_cmp);

    # safety check: ensure $path is within $root
    if (strncmp($path_cmp, $root_cmp, $root_len) !== 0) {
        throw new InvalidArgumentException('Path is not within project root');
    }

    # boundary check: next char must be empty or a separator
    $next_char = substr($path_cmp, $root_len, 1);
    if ($next_char !== '' && $next_char !== $ds) {
        throw new InvalidArgumentException('Path is not within project root');
    }

    # strip project root prefix and leading separator
    $rel = substr($path_norm, $root_len);
    $rel = ltrim($rel, $ds);

    # if somehow empty, treat as project root
    return $rel === '' ? $ds : $rel;
}

/**
 * List files and directories inside the specified path.
 *
 * This function:
 *  - removes "." and ".."
 *  - normalizes directory separators
 *  - sorts folders first
 *  - applies natsort() to folder and file names separately
 *
 * @param string $path Path to scan.
 * @return string[] Sorted list of items (folders first, then files).
 */
function scandir_advanced(string $path): array
{
    $path = str_replace('\\', '/', $path);  # fix Windows dir separators
    $path = rtrim($path, '/\\');            # remove ending slash

    if (!is_dir($path))
    {
        trigger_error("Invalid path to scan! ($path)", E_USER_ERROR);
        return array();
    }

    $folder_items = scandir($path, SCANDIR_SORT_ASCENDING);

    $folders = array();
    $files = array();

    foreach ($folder_items as $name)
    {
        if ($name === '.' || $name === '..') continue;
        $abs_path = $path.'/'.$name;
        if (is_dir($abs_path)) {
            $folders[] = $name;
        } elseif (is_file($abs_path)) {
            $files[] = $name;
        }
    }

    natsort($folders);
    natsort($files);

    return array_merge($folders, $files);
}

/**
 * Recursively remove a directory tree.
 *
 * Silently returns if the path is not a directory or cannot be opened.
 *
 * @param string $path Directory path to remove.
 * @return void
 */
function rmtree(string $path): void
{
    if (!is_dir($path)) return;

    $dir = @opendir($path);
    if ($dir === false) return;  # unable to open directory

    while (false !== ($name = readdir($dir)))
    {
        if ($name === '.' || $name === '..') continue;
        $entry = $path.'/'.$name;
        if (is_dir($entry)) {
            rmtree($entry);
        } elseif (is_file($entry)) {
            @unlink($entry);
        }
    }
    closedir($dir);
    @rmdir($path);
}


/**
 * HTTP / WEB UTILITIES
 * --------------------
 */

/**
 * Get client IP address from server variables.
 *
 * NOTE: This function does not validate or filter the value,
 *       it only returns the raw header content.
 *
 * @return string Detected client IP address or "0.0.0.0" as a fallback.
 */
function get_client_ip(): string
{
    if (isset($_SERVER['HTTP_CLIENT_IP']))
    {
        return (string) $_SERVER['HTTP_CLIENT_IP'];
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        # in case of multiple addresses, return the first one.
        $raw = (string) $_SERVER['HTTP_X_FORWARDED_FOR'];
        $parts = explode(',', $raw);
        return trim($parts[0]);
    }

    if (isset($_SERVER['REMOTE_ADDR']))
    {
        return (string) $_SERVER['REMOTE_ADDR'];
    }

    return '0.0.0.0';
}

/**
 * Perform an HTTP redirect (or JS fallback) and terminate execution.
 *
 * @param string             $location     Target URL.
 * @param int                $status       HTTP status code (must be 3xx).
 * @param string|false|null  $x_redirect_by Optional value for X-Redirect-By header;
 *                                          pass false to skip the header.
 * @return void
 */
function redirect(
            string $location,
            int $status = 302,
            string|false|null $x_redirect_by = CORE_NAME
         ): void
{
    if ($status < 300 || $status > 399) {
        trigger_error(
            'HTTP redirect status code must be a redirection code, 3xx.',
            E_USER_ERROR
        );
    } elseif (!headers_sent()) {  # HTTP redirection
        if (is_string($x_redirect_by)) {
            header('X-Redirect-By: '.$x_redirect_by);
        }
        header('Location: '.$location, true, $status);
    } else {  # JS redirection
        echo "<script type='text/JavaScript'>document.location.href='$location';</script>\n";
    }

    die('<a href="'.$location.'">Please click here if you are not redirected within a few seconds.</a>');
}

/**
 * Reload current page via redirect.
 *
 * @return void
 */
function reload(): void
{
    redirect('?');
}

