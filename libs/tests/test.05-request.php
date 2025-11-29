<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Request parser tests for /libs/inc.request.php */

function core_request_is_ajax_flag_test()
{
    # constant is defined and has boolean type
    core_assert_const('IS_AJAX');
    core_assert_bool(IS_AJAX);

    # value matches current $_REQUEST['ajax'] presence
    core_assert_same(IS_AJAX, array_key_exists('ajax', $_REQUEST));
}

function core_request_registry_structure_test()
{
    # basic keys exist and are strings
    core_assert_true(sys_opt_has_key('request', 'scheme'));
    core_assert_string(sys_opt('request', 'scheme'));

    core_assert_true(sys_opt_has_key('request', 'port'));
    core_assert_string(sys_opt('request', 'port'));

    core_assert_true(sys_opt_has_key('request', 'host'));
    core_assert_string(sys_opt('request', 'host'));

    core_assert_true(sys_opt_has_key('request', 'uri'));
    core_assert_string(sys_opt('request', 'uri'));

    core_assert_true(sys_opt_has_key('request', 'query_raw'));
    core_assert_string(sys_opt('request', 'query_raw'));

    core_assert_true(sys_opt_has_key('request', 'query_str'));
    core_assert_string(sys_opt('request', 'query_str'));

    core_assert_true(sys_opt_has_key('request', 'root'));
    core_assert_string(sys_opt('request', 'root'));

    core_assert_true(sys_opt_has_key('request', 'path'));
    core_assert_string(sys_opt('request', 'path'));

    core_assert_true(sys_opt_has_key('request', 'orig'));
    core_assert_string(sys_opt('request', 'orig'));

    core_assert_true(sys_opt_has_key('request', 'base'));
    core_assert_string(sys_opt('request', 'base'));

    # scheme is expected to be http or https
    $scheme = sys_opt('request', 'scheme');
    core_assert_true(
        $scheme === 'http' || $scheme === 'https',
        'Unexpected request.scheme'
    );

    # port: string, empty or digits only
    $port = sys_opt('request', 'port');
    core_assert_true(is_string($port));
    if ($port !== '') {
        core_assert_true(ctype_digit($port));
    }

    # query_raw: either empty or starts with "?"
    $query_raw = sys_opt('request', 'query_raw');
    if ($query_raw !== '') {
        core_assert_true(str_starts_with($query_raw, '?'));
    }
}

function core_request_registry_relations_test()
{
    $scheme     = sys_opt('request', 'scheme');
    $host       = sys_opt('request', 'host');
    $port       = sys_opt('request', 'port');
    $uri        = sys_opt('request', 'uri');
    $path       = sys_opt('request', 'path');
    $root       = sys_opt('request', 'root');
    $orig       = sys_opt('request', 'orig');
    $base       = sys_opt('request', 'base');
    $query_str  = sys_opt('request', 'query_str');
    $query_raw  = sys_opt('request', 'query_raw');

    # reconstruct root from scheme + host + port
    $expected_root = $scheme.'://'.$host;
    if ($port !== '') {
        $is_default_port =
               ($scheme === 'http'  && $port === '80')
            || ($scheme === 'https' && $port === '443');
        if (!$is_default_port) {
            $expected_root .= ':'.$port;
        }
    }
    core_assert_same($root, $expected_root);

    # orig and base are built from root + uri/path
    core_assert_same($orig, $root.$uri);
    core_assert_same($base, $root.$path);

    # path = REQUEST_URI without GET part and without trailing slashes
    $uri_path = strtok($uri, '?');
    $uri_path = rtrim($uri_path, "/\\");
    core_assert_same($path, $uri_path);

    # query_str is synchronized with part after "?" in uri
    $pos = strpos($uri, '?');
    if ($pos === false) {
        core_assert_same($query_str, '');
    } else {
        $from_uri = substr($uri, $pos + 1);
        core_assert_same($query_str, $from_uri);
    }

    # query_raw is built from query_str
    if ($query_str === '') {
        core_assert_same($query_raw, '');
    } else {
        core_assert_same($query_raw, '?'.$query_str);
    }
}

function core_request_route_registry_test()
{
    # presence of route.* keys in system registry
    core_assert_true(sys_has_key('route.path_raw'));
    core_assert_true(sys_has_key('route.path'));
    core_assert_true(sys_has_key('route.chunks_raw'));
    core_assert_true(sys_has_key('route.chunks'));

    $path_raw    = sys('route.path_raw');
    $path        = sys('route.path');
    $chunks_raw  = sys('route.chunks_raw');
    $chunks      = sys('route.chunks');

    core_assert_string($path_raw);
    core_assert_string($path);
    core_assert_array($chunks_raw);
    core_assert_array($chunks);

    # reverse synthesis: recalc route.path_raw and chunks_raw from request.path
    $request_path    = sys_opt('request', 'path');
    $expected_raw    = route_path_cleanse($request_path);
    $expected_chunks = route_path_parse($expected_raw);

    core_assert_same($path_raw, $expected_raw);
    core_assert_same_arrays($chunks_raw, $expected_chunks);

    # when ML_URL_SUPPORT=false, working values are equal to "raw" ones
    if (!ML_URL_SUPPORT) {
        core_assert_same($path, $path_raw);
        core_assert_same_arrays($chunks, $chunks_raw);
    }
}

function core_request_route_path_cleanse_test()
{
    # valid
    core_assert_equal(route_path_cleanse('/d1/d2/d3/'), '/d1/d2/d3');
    core_assert_equal(route_path_cleanse('/d1/d2/?a=b&c=d'), '/d1/d2');
    # removing GETs
    core_assert_equal(route_path_cleanse('/d1/?a=b&c=d'), '/d1');
    core_assert_equal(route_path_cleanse('/d1/?'), '/d1');
    core_assert_equal(route_path_cleanse('/d1/?/d2/'), '/d1');
    core_assert_equal(route_path_cleanse('/?'), '');
    # removing extension
    core_assert_equal(route_path_cleanse('/d1/d2.d3/d4/?a=b'), '/d1/d2');
    core_assert_equal(route_path_cleanse('/d1/d2.html'), '/d1/d2');
    core_assert_equal(route_path_cleanse('/d1/d2.php/?'), '/d1/d2');
    core_assert_equal(route_path_cleanse('/.ext?'), '');
    core_assert_equal(route_path_cleanse('.?'), '');
    # repeating slashes
    core_assert_equal(route_path_cleanse('/d1//d2//d3'), '/d1');
    core_assert_equal(route_path_cleanse('//d1//d2/'), '');
    core_assert_equal(route_path_cleanse('//////d1/'), '');
    core_assert_equal(route_path_cleanse('/d1///'), '/d1');
}

function core_request_route_path_parse_test()
{
    core_assert_same_arrays(array(), route_path_parse(''));
    core_assert_same_arrays(array(), route_path_parse('/'));
    core_assert_same_arrays(array(), route_path_parse('//'));
    core_assert_same_arrays(array(), route_path_parse('///'));
    core_assert_same_arrays(array('d1', 'd2'), route_path_parse('/d1//d2'));
}

