<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Common functionality */


function bool_to_int($v) { return $v === true ? 1 : 0; }
function int_to_bool($v) { return $v >= 1 ? true : false; }

function bool_to_onoff($v) { return $v === true ? 'On' : 'Off'; }
function bool_to_yesno($v) { return $v === true ? 'Yes' : 'No'; }

function some_to_onoff($v) { return $v == true ? 'On' : 'Off'; }
function some_to_yesno($v) { return $v == true ? 'Yes' : 'No'; }


// Returns a new empty array *by reference*
// so it can be passed to functions expecting `array &...` parameters.
function &empty_array() { $a = array(); return $a; }


function file_get_data($file_path, $var_name='DATA')
{
    if (!is_file($file_path))
    {
        trigger_error('Data file does not exist: '.$file_path, E_USER_ERROR);
        die();
    }
    include($file_path);
    if (!isset($$var_name))
    {
        trigger_error('Data variable is absent: '.$var_name, E_USER_ERROR);
        die();
    }
    return $$var_name;
}


function file_put_data($file_path, $data, $var_name='DATA')
{
    $s = "<?php\n\n$".$var_name.' = '.var_export($data, true).";\n\n";
    file_put_contents($file_path, $s, LOCK_EX);
}


function is_float_or_int($value) { return (is_float($value) || is_int($value)); }
function is_int_or_float($value) { return (is_int($value) || is_float($value)); }

function is_decimal(string $value) { return (false !== strpos($value, '.')); }


/* Return true if the value is an integer or a string representing an integer, false otherwise */
function is_int_like($value)
{
    if (is_int($value)) return true;
    if (!is_string($value)) return false;
    $morp = str_starts_with($value, '-')
         || str_starts_with($value, '+');
    return $morp ? ctype_digit(substr($value, 1)) : ctype_digit($value);
}


/* Trim number to a specific range of values */
function ntrim($value, $min, $max)
{
    if ($value < $min) $value = $min;
    if ($value > $max) $value = $max;
    return $value;
}


function redirect($location, $status=302, $x_redirect_by=CORE_NAME)
{
    if (!is_int($status) || $status < 300 || $status > 399) {
        trigger_error('HTTP redirect status code must be a redirection code, 3xx.', E_USER_ERROR);
    } elseif (!headers_sent()) {  # HTTP redirection
        if (is_string($x_redirect_by)) header('X-Redirect-By: '.$x_redirect_by);
        header('Location: '.$location, true, $status);
    } else {  # JS redirection
        echo "<script type='text/JavaScript'>document.location.href='$location';</script>\n";
    }
    die('<a href="'.$location.'">Please click here if you are not redirected within a few seconds.</a>');
}


function reload()
{
    redirect('?');
}


function abs_to_rel(string $path, string $root=PROJ_ROOT): string
{
    $ds = DIRECTORY_SEPARATOR;

    $normalize = static function (string $p) use ($ds): string {
        // Normalize all separators to the current OS
        $p = str_replace(['/', '\\'], $ds, $p);

        // Collapse duplicate separators (e.g. "//" -> "/")
        $p = preg_replace('~' . preg_quote($ds, '~') . '+~', $ds, $p);

        // On Windows, keep pure drive roots like "C:\"
        if ($ds === '\\') {
            if (preg_match('~^[A-Za-z]:\\\\$~', $p)) {
                return strtoupper($p);
            }
        }

        // Trim trailing separator except for root ("/" or "C:\")
        if (strlen($p) > 1) {
            $p = rtrim($p, $ds);
        }

        return $p;
    };

    $path_norm = $normalize($path);
    $root_norm = $normalize($root);

    // If path is exactly the project root, return a single separator
    if ($path_norm === $root_norm) {
        return $ds;
    }

    // Special case: project root is filesystem root ("/" on Unix)
    if ($root_norm === $ds) {
        $rel = ltrim($path_norm, $ds);
        return $rel === '' ? $ds : $rel;
    }

    $isWindows = ($ds === '\\');

    // Case-insensitive comparison on Windows, case-sensitive on Unix
    $path_cmp = $isWindows ? strtolower($path_norm) : $path_norm;
    $root_cmp = $isWindows ? strtolower($root_norm) : $root_norm;

    $root_len = strlen($root_cmp);

    // Safety check: ensure $path is within $root
    if (strncmp($path_cmp, $root_cmp, $root_len) !== 0) {
        throw new InvalidArgumentException('Path is not within project root');
    }

    // Boundary check: next char must be empty or a separator
    $next_char = substr($path_cmp, $root_len, 1);
    if ($next_char !== '' && $next_char !== $ds) {
        throw new InvalidArgumentException('Path is not within project root');
    }

    // Strip project root prefix and leading separator
    $rel = substr($path_norm, $root_len);
    $rel = ltrim($rel, $ds);

    // If somehow empty, treat as project root
    return $rel === '' ? $ds : $rel;
}


function rmtree($path)
{
    if (!is_dir($path)) return;
    $d = @opendir($path);
    if ($d === false) return;  # handle error
    while (false !== ($name = readdir($d)))
    {
        if (($name != '.') && ($name != '..'))
        {
            $p = $path.'/'.$name;
            if (is_dir($p)) rmtree($p); else unlink($p);
        }
    }
    closedir($d);
    rmdir($path);
}


/**
 * List files and directories inside the specified path
 * This function:
 * + removes "." and ".."
 * + sorts folders first
 * + applies natsort to folder and file names
 */
function scandir_advanced($path)
{
    $path = str_replace('\\', '/', $path);  # fix Windows dir separators
    $path = rtrim($path, '/\\');            # remove ending slash

    if (!is_dir($path))
    {
        trigger_error("Invalid path to scan! ($path)", E_USER_ERROR);
        return array();
    }

    $files = scandir($path, SCANDIR_SORT_ASCENDING);

    $just_folders = array();
    $just_files = array();
    foreach ($files as $fname)
    {
        if ($fname == '.' || $fname == '..') continue;
        $abs_path = $path.'/'.$fname;
        if (is_dir($abs_path)) array_push($just_folders, $fname);
        if (is_file($abs_path)) array_push($just_files, $fname);
    }
    natsort($just_folders);
    natsort($just_files);

    return array_merge($just_folders, $just_files);
}

function get_client_ip()
{
    if (isset($_SERVER['HTTP_CLIENT_IP'])
     && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
    {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        // split addresses and get 1st
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($ips as $ip_address)
        {
            $ip_address = trim($ip_address); // remove spaces
            if (filter_var($ip_address, FILTER_VALIDATE_IP))
            {
                return $ip_address;
            }
        }
    }

    if (isset($_SERVER['REMOTE_ADDR'])
     && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP))
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    return '';
}

