<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Request parser API */

# Request type
define('IS_AJAX', array_key_exists('ajax', $_REQUEST));

# Server
$f_ssl = (($_SERVER['HTTPS'] ?? 'off') === 'on')
       || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

$scheme = $f_ssl ? 'https' : 'http';

$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
$port = (string)($_SERVER['SERVER_PORT'] ?? '');

// strip port from HTTP_HOST if present (IPv4 / hostname only)
if (strpos($host, ':') !== false && $host[0] !== '[') {
    [$host_name, $host_port] = explode(':', $host, 2);
    if ($port === '' && ctype_digit($host_port)) {
        $port = $host_port;
    }
    $host = $host_name;
}

$root = $scheme.'://'.$host;
if ($port !== ''
 && !(($scheme === 'http'  && $port === '80')
   || ($scheme === 'https' && $port === '443')))
{
    $root .= ':'.$port;
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';

sys_opt('request', 'scheme', $scheme);
sys_opt('request', 'port', $port);
sys_opt('request', 'host', $host);
sys_opt('request', 'uri', $uri);

$query_raw = $_SERVER['QUERY_STRING'] ?? '';
$query = ltrim($query_raw, '?');         # avoid multiple "?"
if ($query !== '') $query = '?'.$query;  # add ? to non empty query

sys_opt('request', 'query_raw', $query);
sys_opt('request', 'query_str', $query_raw);

# Common
sys_opt('request', 'root', $root);
sys_opt('request', 'path', rtrim(strtok($uri, '?'), '/\\'));
sys_opt('request', 'orig', $root.sys_opt('request', 'uri'));
sys_opt('request', 'base', $root.sys_opt('request', 'path'));

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
    if (!empty($url_path)                # if not empty
    && !str_starts_with($url_path, '/')  # .. and not started with slash
    && $auto_add_leading_slash)          # .. and option enabled
    {
        $url_path = '/'.$url_path;      # add leading slash
    }

    $noget = strtok($url_path, '?');    # remove GETs
    if (false === $noget) $noget = '';

    $noext = strtok($noget, '.');       # no dots or extensions allowed
    if (false === $noext) $noext = '';

    # no double slashes allowed
    $nodbl = (false === $p = strpos($noext, '//')) ? $noext : substr($noext, 0, $p);

    $url_path = rtrim($nodbl, '/\\');   # remove extra slashes

    if (empty($url_path) && $empty_path_slashed) $url_path = '/';  # add slash to empty path

    if (!empty($url_path)              # if not empty
    && !str_ends_with($url_path, '/')  # .. and not ends with slash
    && $add_ending_slash)              # .. and option enabled
    {
        $url_path .= '/';  # add trailing slash
    }

    return $url_path;
}


# Parse incoming request into chunks
function code_path_parse(string $url_clean_path)
{
    $chunks = explode('/', $url_clean_path);
    $chunks = array_filter($chunks, static function ($v) {
        return $v !== '';
    });
    return array_values($chunks);
}

