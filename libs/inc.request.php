<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Request parser API */

# Request type
define('IS_AJAX', array_key_exists('ajax', $_REQUEST));

# Server
$f_ssl = (($_SERVER['HTTPS'] ?? 'off') == 'on');
$query = ltrim($_SERVER['QUERY_STRING'], '?');  # avoid multiply "?"
if (!empty($query)) $query = '?'.$query;        # add ? to non empty query

sys_opt('request', 'scheme', ($f_ssl) ? 'https' : 'http');
sys_opt('request', 'port', $_SERVER['SERVER_PORT']);
sys_opt('request', 'host', $_SERVER['SERVER_NAME']);
sys_opt('request', 'uri', $_SERVER['REQUEST_URI']);
sys_opt('request', 'query_raw', $query);
sys_opt('request', 'query_str', $_SERVER['QUERY_STRING']);

# Common
sys_opt('request', 'root', sys_opt('request', 'scheme').'://'.sys_opt('request', 'host'));
sys_opt('request', 'path', rtrim(strtok($_SERVER["REQUEST_URI"], '?'), '/\\'));
sys_opt('request', 'orig', sys_opt('request', 'root').sys_opt('request', 'uri'));
sys_opt('request', 'base', sys_opt('request', 'root').sys_opt('request', 'path'));

# Core
sys('route.path_raw', code_path_cleanse(sys_opt('request', 'path')));  # original route path
sys('route.path', sys('route.path_raw'));
sys('route.chunks_raw', code_path_parse(sys('route.path')));  # original route chunks
sys('route.chunks', sys('route.chunks_raw'));

# Multilingual URL support
if (ML_URL_SUPPORT)
{
    $chunks = sys('route.chunks');
    if (array_key_exists(0, $chunks))  # if there is something..
    {
        unset($chunks[0]);                # remove 1st chunk
        $chunks = array_values($chunks);  # reindex
        $path = code_path_cleanse(implode('/', $chunks));
        sys('route.path', $path);         # update string value
        sys('route.chunks', $chunks);     # update array value
    }
}


function code_path_cleanse(string $url_path,
                           bool $auto_add_leading_slash=true,
                           bool $empty_path_slashed=false,
                           bool $add_ending_slash=false)
{
    if (!empty($url_path)                 # if not empty
    and !str_starts_with($url_path, '/')  # .. and not started with slash
    and $auto_add_leading_slash)          # .. and option enabled
    {
        $url_path = '/'.$url_path;      # add leading slash
    }

    $noget = strtok($url_path, '?');    # remove GETs
    if (false === $noget) $noget = '';

    $noext = strtok($noget, '.');       # no dotts or extensions allowed
    if (false === $noext) $noext = '';

    # no double slashes allowed
    $nodbl = (false === $p = strpos($noext, '//')) ? $noext : substr($noext, 0, $p);
    if (false === $nodbl) $nodbl = '';

    $url_path = rtrim($nodbl, '/\\');   # remove extra slashes

    if (empty($url_path) and $empty_path_slashed) $url_path = '/';  # add slash to empty path

    if (!empty($url_path)               # if not empty
    and !str_ends_with($url_path, '/')  # .. and not ends with slash
    and $add_ending_slash)              # .. and option enabled
    {
        $url_path .= '/';  # add finalizing slash
    }

    return $url_path;
}


# Parse incoming request into chunks
function code_path_parse(string $url_clean_path)
{
    return array_values(array_filter(explode('/', $url_clean_path)));
}

