<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Response router API */

define('HDL_NONE', 0);   # Nothing
define('HDL_ACT' , 1);   # Action
define('HDL_TPL' , 2);   # Template
define('HDL_CSS' , 4);   # CSS
define('HDL_JS'  , 8);   # Javascript
define('HDL_INIT', 16);  # Force static files to be at the top of stack

# HDL_INIT flag instructs to put handler static files to the initial sub-stack and
# then push every next flagged handler static files to the end of this sub-stack

define('HDL_CSS_JS',      HDL_CSS | HDL_JS);

define('HDL_ACT_CSS',     HDL_ACT | HDL_CSS);
define('HDL_ACT_JS',      HDL_ACT | HDL_JS);
define('HDL_ACT_CSS_JS',  HDL_ACT | HDL_CSS | HDL_JS);

define('HDL_TPL_CSS',     HDL_TPL | HDL_CSS);
define('HDL_TPL_JS',      HDL_TPL | HDL_JS);
define('HDL_TPL_CSS_JS',  HDL_TPL | HDL_CSS | HDL_JS);

define('HDL_ACT_TPL',     HDL_ACT | HDL_TPL);
define('HDL_ACT_TPL_CSS', HDL_ACT | HDL_TPL | HDL_CSS);
define('HDL_ACT_TPL_JS',  HDL_ACT | HDL_TPL | HDL_JS);

define('HDL_ACT_TPL_CSS_JS', HDL_ACT | HDL_TPL | HDL_CSS | HDL_JS);
define('HDL_ALL', HDL_ACT_TPL_CSS_JS);  # synonym

define('DEF_HDL_FNAME', 'default');
define('COM_INI_FNAME', '__init__.php');
define('COM_INF_FNAME', '__info__.php');
define('DEF_API_FNAME', 'api.main.php');

define('HDL_TRY_DEF', 32);  # append DEF_HDL_FNAME to path first
define('HDL_ASC_DEF', 64);  # ascent to top default on fail

$_H_PRELOAD_CSS_INIT_POS = 0;  # hdl.preload.css initial sub-stack last position
$_H_PRELOAD_JS_INIT_POS = 0;   # hdl.preload.js initial sub-stack last position


/**
 * TERMINATION API
 * ---------------
 */

/**
 * Internal signal to immediately terminate current route processing.
 *
 * Optionally carries redirect parameters to be applied after finalization.
 */
class CoreTerminateRoute extends RuntimeException
{
    public ?string $redirect_url;
    public ?int $redirect_status;
    public ?string $redirect_by;

    /**
     * @param string         $message         Human-readable reason of termination.
     * @param int            $code            Optional internal error code.
     * @param Throwable|null $previous        Chained previous exception.
     * @param string|null    $redirect_url    Target URL for redirect, if any.
     * @param int|null       $redirect_status HTTP status code for redirect.
     * @param string|null    $redirect_by     Optional redirect source marker.
     */
    public function __construct(string $message = 'Route terminated',
                                   int $code = 0,
                            ?Throwable $previous = null,
                               ?string $redirect_url = null,
                                  ?int $redirect_status = null,
                               ?string $redirect_by = null)
    {
        parent::__construct($message, $code, $previous);
        $this->redirect_url    = $redirect_url;
        $this->redirect_status = $redirect_status;
        $this->redirect_by     = $redirect_by;
    }

    /**
     * Check whether this termination carries redirect data.
     *
     * @return bool True when redirect_url is not null.
     */
    public function has_redirect(): bool
    {
        return $this->redirect_url !== null;
    }
}

/**
 * Emergency route termination without redirect.
 *
 * Can be used in handlers, templates, APIs, etc.
 *
 * @param string $message Reason of route termination (for diagnostics).
 * @return void
 *
 * @throws CoreTerminateRoute
 */
function core_terminate_route(string $message = 'Route terminated'): void
{
    throw new CoreTerminateRoute($message);
}

/**
 * Emergency route termination with final redirect.
 *
 * @param string      $location      Target URL.
 * @param int         $status        HTTP redirect status code.
 * @param string|null $x_redirect_by Optional redirect source marker.
 * @param string      $message       Reason text for diagnostics.
 * @return void
 *
 * @throws CoreTerminateRoute
 */
function core_terminate_and_redirect(
            string $location,
               int $status = 302,
           ?string $x_redirect_by = CORE_NAME,
            string $message = 'Route terminated with redirect'
         ): void
{
    throw new CoreTerminateRoute($message, 0, null, $location, $status, $x_redirect_by);
}


/**
 * ROUTE ROOT API
 * --------------
 */

$_ROUTE_ROOT = COMS_ROOT;
$_ROUTE_ROOT_STACK = array();

/**
 * Restore previous route root from the internal stack.
 *
 * @return bool True on success, false when the stack is empty.
 */
function restore_route_root(): bool
{
    global $_ROUTE_ROOT, $_ROUTE_ROOT_STACK;
    if (empty($_ROUTE_ROOT_STACK)) return false;
    $_ROUTE_ROOT = array_pop($_ROUTE_ROOT_STACK);
    return true;
}

/**
 * Get current route root (components tree root).
 *
 * @return string Absolute path to current route root.
 */
function get_route_root(): string
{
    return $GLOBALS['_ROUTE_ROOT'];
}

/**
 * Set current route root and optionally store previous root.
 *
 * @param string|null $new_root   New absolute route root; null keeps current.
 * @param bool        $auto_store When true, push previous root into stack.
 * @return string Current route root after change.
 */
function set_route_root(
            ?string $new_root = null,
               bool $auto_store = true
         ): string
{
    global $_ROUTE_ROOT, $_ROUTE_ROOT_STACK;
    if ($auto_store) array_push($_ROUTE_ROOT_STACK, $_ROUTE_ROOT);
    if (!is_null($new_root)) $_ROUTE_ROOT = $new_root;  # set new
    return $_ROUTE_ROOT;
}

/**
 * Push current route root into the internal stack without changing it.
 *
 * @return string Current route root.
 */
function store_route_root(): string
{
    global $_ROUTE_ROOT, $_ROUTE_ROOT_STACK;
    array_push($_ROUTE_ROOT_STACK, $_ROUTE_ROOT);  # store current
    return $_ROUTE_ROOT;
}


/**
 * COMMON
 * ------
 */

/**
 * Represent handler flag bitmask as a human-readable string.
 *
 * @param int $flags Handler flags bitmask (HDL_*).
 * @return string Printable representation like "<act|tpl>" or "<all>".
 */
function _hdl_to_str(int $flags): string
{
    if ($flags === HDL_NONE) return '<none>';

    if (($flags & HDL_ALL) === HDL_ALL) return '<all>';

    $s = '';
    if ($flags & HDL_ACT) $s .= '|act';
    if ($flags & HDL_TPL) $s .= '|tpl';
    if ($flags & HDL_CSS) $s .= '|css';
    if ($flags & HDL_JS)  $s .= '|js';
    return '<'.trim($s, '|').'>';
}

/**
 * Recursively load translation files from a given language directory.
 *
 * Each PHP file is expected to populate global $DICTIONARY.
 *
 * @param string $lng_path Absolute path to language directory.
 * @return void
 */
function core_load_translations(string $lng_path): void
{
    global $DICTIONARY;
    if (!isset($DICTIONARY)) $DICTIONARY = array();  # init
    if (!is_dir($lng_path)) return;
    $file_list = scandir($lng_path, SCANDIR_SORT_NONE);  # order doesn't matter
    foreach($file_list as $file_name)
    {
        if ($file_name == '.' or $file_name == '..') continue;
        $new_path = $lng_path.'/'.$file_name;
        if (is_file($new_path)) {
            $GLOBALS['SYS']['included'][] = $lng_path.'/'.$file_name;
            require($lng_path.'/'.$file_name);
        } elseif (is_dir($new_path)) {
            core_load_translations($new_path);  # recursion!
        }
    }
}

/**
 * Build filesystem path for component/handler element.
 *
 * @param string|null $root   Base directory (defaults to current route root).
 * @param string      $path   Relative handler path ("comp.sub" or "/comp/sub").
 * @param string      $name   File base name without prefix/suffix.
 * @param string      $prefix Optional dot-separated prefix (e.g. "act").
 * @param string      $suffix Optional extension (e.g. "php").
 * @return string Absolute (or route-relative) path.
 */
function _route_mk_path(
           ?string $root = null,
            string $path = '',
            string $name = '',
            string $prefix = '',
            string $suffix = ''
         ): string
{
    global $_ROUTE_ROOT;
    if (is_null($root)) $root = $_ROUTE_ROOT;
    $path = _route_std_path($path);
    if (!empty($prefix)) $prefix = trim($prefix, '.').'.';
    if (!empty($suffix)) $suffix = '.'.trim($suffix, '.');
    return $root.$path.'/'.$prefix.$name.$suffix;
}

/**
 * Router path standardization.
 *
 * Converts dot-notation ('p1.p2.p3') into '/p1/p2/p3', truncates at the first
 * "//" and returns either '' or a string starting with "/".
 *
 * @param string $path Raw handler path.
 * @return string Normalized path starting with "/" or empty string.
 */
function _route_std_path(string $path): string
{
    $dtos = str_replace('.', '/', $path);  # replace "." to "/"

    # no double slashes allowed: truncate at the first occurrence of "//"
    $nodbl = (false === $p = strpos($dtos, '//')) ? $dtos : substr($dtos, 0, $p);

    $result = '/'.trim($nodbl, '/\\');
    return ($result != '/') ? $result : '';
}

/**
 * ROUTING API
 * -----------
 */


/**
 * Initialize component API and its dependencies.
 *
 * $target_path may be:
 *  - component alias ("/component" or "component");
 *  - full handler path ("component.sub.handler"), in this case
 *    language and init files are processed along the path.
 *
 * @param string $target_path Component alias or handler path.
 * @return void
 */
function core_use_api(string $target_path): void
{
    global $_ROUTE_ROOT;
    $target_path = _route_std_path($target_path);
    $parts = explode('/', $target_path);

    $path = '';
    foreach($parts as $level => $part)
    {
        if ($part === '') continue;
        $path .= rtrim('/'.$part, '/');

        $lng_path = $_ROUTE_ROOT.$path.'/_lang';
        $inf_file = _route_mk_path($_ROUTE_ROOT, $path, COM_INF_FNAME);
        $ini_file = _route_mk_path($_ROUTE_ROOT, $path, COM_INI_FNAME);
        $api_file = _route_mk_path($_ROUTE_ROOT, $path, DEF_API_FNAME);

        # LNG files can be on any level
        if (ML_DIR_SUPPORT
        and !sys_opt_has_value('lng.loaded', $lng_path)
        and is_dir($lng_path))
        {
            core_load_translations($lng_path);
            sys_opt_push_unique('lng.loaded', $lng_path);
        }

        # INF files are on 1st level only
        if ($level == 1 and !sys_opt_has_value('inf.loaded', $inf_file))
        {
            if (!is_file($inf_file)) {
                trigger_error('Missed component information file: '.$inf_file, E_USER_ERROR);
            }
            $GLOBALS['SYS']['included'][] = $inf_file;
            require($inf_file);
            if (!isset($CINF)) {
                trigger_error('Invalid component file format! File: '.$inf_file, E_USER_ERROR);
            } else {
                sys_opt_push_unique('inf.loaded', $inf_file);
                $requires = $CINF['require'] ?? array();
                foreach($requires as $alias) core_use_api($alias);
                unset($CINF);
            }
        }

        # INI files can be on any level
        if (!sys_opt_has_value('ini.loaded', $ini_file)
        and is_file($ini_file))
        {
            require_once($ini_file);
            sys_opt_push_unique('ini.loaded', $ini_file);
        }

        # API files are on 1 level only
        if ($level == 1
        and !sys_opt_has_value('api.loaded', $api_file)
        and is_file($api_file))
        {
            require_once($api_file);
            sys_opt_push_unique('api.loaded', $api_file);
        }
    }
}

# Hint: To skip passing $CONTEXT parameter, use empty_array(), e.g.:
# core_use_handler('...', empty_array(), HDL_TPL|HDL_TRY_DEF|HDL_ASC_DEF);
#
# Note: empty_array() function defined in /libs/inc.common.php
#
# Core $CONTEXT options:
#   CORE_EXTRACT_VARS - force context vars extract to handler's namespace
#   CORE_ENABLE_BUFFER - enables output buffering during handler's processing
#   CORE_RETURN_OUTPUT - force output buffer return
# --
# >by default output buffering and extracting vars are turned off
# >those parameters inaccessible from handler itself
#
# Return value:
#   false  - no handler files found (according to $flags)
#   true   - handler found, but CORE_RETURN_OUTPUT is not set
#   string - handler found and output buffer returned (CORE_RETURN_OUTPUT=true)
#
# IMPORTANT: always check result with strict comparison (=== false / === true)
#            to distinguish boolean and string results.
#

/**
 * Resolve and execute handler by path.
 *
 * @param string      $path    Handler path in dotted or slash notation.
 * @param array       $CONTEXT Shared context passed by reference into handler scope.
 * @param int         $flags   HDL_* flag bitmask controlling search and static preload.
 * @param string|null $_name   Internal handler name override (do not use directly).
 * @param bool        $_n1st   Internal flag marking first ascent attempt.
 * @return bool|string False when handler not found,
 *                     true when found but no buffered output returned,
 *                     string when buffered output is returned.
 */
function core_use_handler(
            string $path,
             array &$CONTEXT = array(),
               int $flags = HDL_ALL | HDL_ASC_DEF | HDL_TRY_DEF,
           ?string $_name = null,  # handler name (internal)
              bool $_n1st = false  # native 1st occurrence (internal)
         ): bool|string
{
    global $_H_PRELOAD_CSS_INIT_POS,
           $_H_PRELOAD_JS_INIT_POS,
           $_ROUTE_ROOT;

    $path = _route_std_path($path);

    # check handler type flags
    if (!( $flags & (HDL_ACT | HDL_TPL | HDL_CSS | HDL_JS) )) {
        trigger_error('Target handler type undefined!', E_USER_WARNING);
    }

    # check routing flags
    if (($flags & HDL_ASC_DEF) and !($flags & HDL_TRY_DEF)) {
        trigger_error('Searching default handlers allowed but their usage is not!', E_USER_NOTICE);
    }

    if (!is_null($_name))  # if not coordinational function call..
    {
        $stack_name = $path.' > '.$_name.' '._hdl_to_str($flags);
        sys_opt_add('route.stack', $stack_name, _hdl_to_str(HDL_NONE));
    }

    if (is_dir($_ROUTE_ROOT.$path))  # given path exists
    {
        if (is_null($_name))  # no name given
        {
            if ($flags & HDL_TRY_DEF)  # try default if allowed
            {
                $new_flags = $flags & ~HDL_TRY_DEF & ~HDL_ASC_DEF;  # single try

                $result = core_use_handler($path, $CONTEXT, $new_flags, DEF_HDL_FNAME, $_n1st);
                if (false !== $result) return $result;  # return positive result only
            }
        }
        else  # name given
        {
            $handler_return = NULL;  # for output buffering purposes
            $handler_found = HDL_NONE;

            $act_path = _route_mk_path($_ROUTE_ROOT, $path, $_name, 'act', 'php');
            $tpl_path = _route_mk_path($_ROUTE_ROOT, $path, $_name, 'tpl', 'php');

            $css_path = _route_mk_path($_ROUTE_ROOT, $path.'/web/s', $_name, '', 'css');
            $css_url_path = _route_mk_path('', $path.'/web/s', $_name, '', 'css');
            $css_url_root = sys_opt_get('request', 'root').COMS_PATH.$css_url_path;

            $js_path = _route_mk_path($_ROUTE_ROOT, $path.'/web/s', $_name, '', 'js');
            $js_url_path = _route_mk_path('', $path.'/web/s', $_name, '', 'js');
            $js_url_root = sys_opt_get('request', 'root').COMS_PATH.$js_url_path;

            if ($flags & HDL_ACT and is_file($act_path)) $handler_found |= HDL_ACT;
            if ($flags & HDL_TPL and is_file($tpl_path)) $handler_found |= HDL_TPL;
            if ($flags & HDL_CSS and is_file($css_path)) $handler_found |= HDL_CSS;
            if ($flags & HDL_JS  and is_file($js_path))  $handler_found |= HDL_JS;

            if ($handler_found)
            {
                sys_opt_set('route.stack', $stack_name, _hdl_to_str($handler_found));

                if ($handler_found & (HDL_ACT | HDL_TPL)) core_use_api($path);

                if ($handler_found & HDL_CSS) {
                    if ($flags & HDL_INIT) {  # initial-type static handler
                        sys_opt_insert('hdl.preload.css', $_H_PRELOAD_CSS_INIT_POS++, $css_url_root);
                    } else {  # regular-type static handler
                        sys_opt_push_unique('hdl.preload.css', $css_url_root);
                    }
                }
                if ($handler_found & HDL_JS) {
                    if ($flags & HDL_INIT) {  # initial-type static handler
                        sys_opt_insert('hdl.preload.js', $_H_PRELOAD_JS_INIT_POS++, $js_url_root);
                    } else {  # regular-type static handler
                        sys_opt_push_unique('hdl.preload.js', $js_url_root);
                    }
                }

                # Launch ACT & TPL files inside a separate namespace, but they
                # both still have an access to a single and outer context!
                # At the same time we hides the handler from this function namespace.
                (function (
                        int $handler_found,
                     string $act_path,
                     string $tpl_path,
                      array &$CONTEXT,
                    ?string &$handler_return
                 ): void
                {
                    $enable_buffer = $CONTEXT['CORE_ENABLE_BUFFER'] ?? false;  # enable buffering
                    unset($CONTEXT['CORE_ENABLE_BUFFER']);

                    $return_output = $CONTEXT['CORE_RETURN_OUTPUT'] ?? false;  # force buffer return
                    unset($CONTEXT['CORE_RETURN_OUTPUT']);

                    if ($return_output) $enable_buffer = true;  # if forced buffer return, then buffer must be enabled

                    if ($enable_buffer) ob_start();  # start buffering

                    if (isset($CONTEXT['CORE_EXTRACT_VARS'])) {
                        unset($CONTEXT['CORE_EXTRACT_VARS']);
                        extract($CONTEXT, EXTR_OVERWRITE);
                    }
                    if ($handler_found & HDL_ACT) {
                        $GLOBALS['SYS']['included'][] = $act_path;
                        require($act_path);
                        sys_opt_push_unique('hdl.loaded', $act_path);
                    }
                    if ($handler_found & HDL_TPL) {
                        $GLOBALS['SYS']['included'][] = $tpl_path;
                        require($tpl_path);
                        sys_opt_push_unique('hdl.loaded', $tpl_path);
                    }

                    if ($enable_buffer) {      # if output buffering enabled..
                        if ($return_output) {  #   if forced buffer return
                            $handler_return = ob_get_clean();  # get buffer and turn it off
                        } else {               #   otherwise
                            ob_end_flush();    #     output buffer and turn it off
                        }
                    }

                })($handler_found, $act_path, $tpl_path, $CONTEXT, $handler_return);
            }
            #
            # Note: non-null $handler_return means that handler has been found
            #       and output buffering with CORE_RETURN_OUTPUT was enabled.
            # In this case we're using it as an indicator of success.
            # Result:
            #   false  - handler not found
            #   true   - handler found, but nothing returned (no buffer result)
            #   string - handler found and its buffered output returned
            # IMPORTANT: when checking result, use strict comparison:
            #   if (false === core_use_handler(...)) { /* not found */ }
            #
            return !is_null($handler_return) ? $handler_return : boolval($handler_found);
        }
    }

    # try to extract new name from given path
    $new_name = substr($path, strrpos($path, '/') + 1);  # extract last dir name
    $new_path = substr($path, 0, strlen($path) - strlen($new_name) - 1);  # cut path

    if (is_dir($_ROUTE_ROOT.$new_path))  # new path exists
    {
        # try new name, but on top level only (1st time)
        if (!empty($new_name) and !$_n1st)
        {
            $result = core_use_handler($new_path, $CONTEXT, $flags, $new_name, $_n1st);
            if (false !== $result) return $result;  # return positive result only
            $_n1st = true;
        }
    }

    # try to get higher, if there was a way and if allowed
    if (!empty($path) and $flags & HDL_ASC_DEF)
    {
        $result = core_use_handler($new_path, $CONTEXT, $flags, NULL, $_n1st);
        if (false !== $result) return $result;  # return positive result only
    }

    return false;  # shit happens.. :)
}

/**
 * Build external URL by handler path.
 *
 * @param string $handler_path Handler path in dotted or slash notation.
 * @return string Absolute URL using current request root.
 */
function core_route_path_to_url(string $handler_path): string
{
    $path = _route_std_path($handler_path);
    return sys_opt_get('request', 'root') . $path;
}

#
# Build static URL path/root

/**
 * Alias to core_static_url_path().
 *
 * @param string|null $dir Base template directory (defaults to COMS_ROOT).
 * @return string URL path to /web directory.
 */
function core_static_url(?string $dir = null): string
{
    return core_static_url_path($dir);
}

/**
 * Build URL path to static (/web) folder of the template.
 *
 * @param string|null $tpl_path Filesystem path to template directory (usually __DIR__).
 *                              Null falls back to COMS_ROOT.
 * @return string URL path to /web folder (without trailing slash).
 */
function core_static_url_path(?string $tpl_path = null): string
{
    if (is_null($tpl_path)) $tpl_path = COMS_ROOT;
    $tpl_path = str_replace('\\', '/', $tpl_path);

    $path = str_replace(PROJ_ROOT, '', $tpl_path);
    $path .= '/web';

    return $path;
}

/**
 * Build absolute URL to static (/web) folder of the template.
 *
 * @param string|null $tpl_path Filesystem path to template directory (usually __DIR__).
 * @return string Absolute URL to /web folder (without trailing slash).
 */
function core_static_url_root(?string $tpl_path = null): string
{
    return sys_opt_get('request', 'root').core_static_url_path($tpl_path);
}

