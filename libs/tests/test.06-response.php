<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Response router tests for /libs/inc.response.php */


define('MAX_DIR_NUMBER', 3);
define('MAX_DIR_DEPTH', 5);

define('DIR_NAME_PREFIX', 'd');

define('TEST_URL_ROOT', sys_opt_get('request', 'root').COMS_PATH);

/*-------------------------------------------------------------+
.                 TEST FILE SYSTEM STRUCTURE                   .
.    Legend: n - brunch number; d - folder depth (0 - root)    .
+-----+------------------+------------------+------------------+
|  \  |        n=1       |        n=2       |        n=3       |
+-----+------------------+------------------+------------------+
|     |                         /root/                         |
|     |    __init__.php; act.default.php; tpl.default.php;     |
| d=0 |          web/s/default.css; web/s/default.js;          |
|     |    act.header.php; tpl.header.php; tpl.footer.php;     |
|     |          web/s/header.css; web/s/footer.css            |
+-----+------------------+------------------+------------------+
|     |      /d11/       |      /d21/       |      /d31/       |
|     | __info__.php     | __info__.php     | __info__.php     |
|     | __init__.php     | __init__.php     |                  |
| d=1 | api.main.php     | api.main.php     |                  |
|     | act.default.php  | act.test21.php   |                  |
|     | tpl.default.php  |                  |                  |
|     | web/s/test11.css |                  |                  |
|     | web/s/test11.js  |                  |                  |
+-----+------------------+------------------+------------------+
|     |      /d12/       |       /d22/      |      /d32/       |
| d=2 | act.default.php  | act.test22.php   |                  |
|     | tpl.default.php  | web/s/test22.css |                  |
|     |use(header/footer)| web/s/test22.js  |                  |
+-----+------------------+------------------+------------------+
|     |      /d13/       |       /d23/      |      /d33/       |
| d=3 | act.default.php  | act.test23.php   | web/s/test33.css |
|     | tpl.default.php  | tpl.test23.php   | web/s/test33.js  |
+-----+------------------+------------------+------------------+
|     |      /d14/       |       /d24/      |      /d34/       |
| d=4 | act.default.php  | tpl.test24.php   |                  |
|     | tpl.default.php  |use(header/footer)|                  |
+-----+------------------+------------------+------------------+
|     |      /d15/       |       /d25/      |      /d35/       |
| d=5 | act.default.php  | tpl.test25.php   |                  |
|     | tpl.default.php  | use(d11.d12)     |                  |
+-----+------------------+------------------+------------------+
. [!] d11/__info__.php requires: d21                           .
. [!] d21/__info__.php requires: d11, d31                      .
+--------------------------------------------------------------*/


/**
 * TESTING API
 * -----------
 */

function _gen_cinf_file($n, $d, $requires=array())
{
    $cinf_arr = array(
        'alias' => DIR_NAME_PREFIX.$n.$d,
        'title' => "Test component $n:$d",
        'descr' => '',
        'prefix' => DIR_NAME_PREFIX.$n.$d.'_',
        'require' => $requires,
        'version' => 0.1,
        'build' => date('YmdHis'),
    );
    return "<?php\n\n".'$CINF = '.var_export($cinf_arr, true).";\n\n?>";
}

function _gen_use_file(array $uses = array())
{
    $s = '';
    foreach($uses as $path) {
        $s .= "core_use_handler('$path');\n";
    }
    return "<?php\n\n".$s."\n?>";
}

function _create_file($path, $content='')
{
    if (isset($_REQUEST['verbose'])) { print("Create text file: <b>{$path}</b><br>\n"); }
    file_put_contents($path, $content);
}

function _build_test_dir($path, $n, $d)
{
    $root = get_route_root().$path;

    if (is_dir($root)) return;
    if (isset($_REQUEST['verbose'])) { print("Create folder: <b>{$root}</b><br>\n"); }
    mkdir($root);

    if ($d == 0)  # Top level
    {
        _create_file($root.'/__init__.php');
        _create_file($root.'/act.default.php');
        _create_file($root.'/tpl.default.php');
        _create_file($root.'/act.header.php');
        _create_file($root.'/tpl.header.php');
        _create_file($root.'/tpl.footer.php');
        mkdir($root.'/web/s', 0777, true);
        _create_file($root.'/web/s/default.css');
        _create_file($root.'/web/s/default.js');
        _create_file($root.'/web/s/header.css');
        _create_file($root.'/web/s/footer.css');
    }
    else if ($d == 1)  # Component level
    {
        if ($n == 1) {
            $requires = array('d21');
            _create_file($root.'/__info__.php', _gen_cinf_file($n, $d, $requires));
            _create_file($root.'/__init__.php');
            _create_file($root.'/api.main.php');
            _create_file($root.'/act.default.php');
            _create_file($root.'/tpl.default.php');
        }
        else if ($n == 2)
        {
            $requires = array('d11', 'd31');
            _create_file($root.'/__info__.php', _gen_cinf_file($n, $d, $requires));
            _create_file($root.'/__init__.php');
            _create_file($root.'/api.main.php');
            _create_file($root.'/act.test21.php');
        }
        else if ($n == 3)
        {
            _create_file($root.'/__info__.php', _gen_cinf_file($n, $d, array()));
        }
    }
    else  # Sub level
    {
        if ($n == 1)
        {
            if ($d == 2) {
                _create_file($root.'/act.default.php');
                $uses = array('header', 'footer');
                _create_file($root."/tpl.default.php", _gen_use_file($uses));
            } else {
                _create_file($root.'/act.default.php');
                _create_file($root.'/tpl.default.php');
            }
        }
        else if ($n == 2)
        {
            if ($d <= 3) {
                _create_file($root."/act.test{$n}{$d}.php");
            }
            if ($d == 3) {
                _create_file($root."/tpl.test{$n}{$d}.php");
            }
            if ($d == 4) {
                $uses = array('header', 'footer');
                _create_file($root."/tpl.test{$n}{$d}.php", _gen_use_file($uses));
            }
            if ($d == 5) {
                $uses = array('d11.d12');
                _create_file($root."/tpl.test{$n}{$d}.php", _gen_use_file($uses));
            }
        }
    }

    if ($n === $d and $n >= 1 and $n <=3)  # statics
    {
        mkdir($root.'/web/s', 0777, true);
        _create_file($root."/web/s/test{$n}{$d}.css");
        _create_file($root."/web/s/test{$n}{$d}.js");
    }
}

function _build_test_tree()
{
    _build_test_dir('', 0, 0);
    for($number = 1; $number <= MAX_DIR_NUMBER; $number++)
    {
        $path = '';
        for($depth = 1; $depth <= MAX_DIR_DEPTH; $depth++)
        {
            $path .= "/".DIR_NAME_PREFIX.$number.$depth;
            _build_test_dir($path, $number, $depth);
        }
    }
}

function _remove_test_tree()
{
    if (isset($_REQUEST['verbose'])) { print("Remove folder: <b>".get_route_root()."</b><br>\n"); }
    rmtree(get_route_root());
}


/**
 * COMMON TESTS
 * ------------
 */

function response_constants_test()
{
    core_assert_const_and_same('HDL_NONE', 0);
    core_assert_const_and_same('HDL_ACT', 1);
    core_assert_const_and_same('HDL_TPL', 2);
    core_assert_const_and_same('HDL_CSS', 4);
    core_assert_const_and_same('HDL_JS', 8);
    core_assert_const_and_same('HDL_INIT', 16);

    core_assert_const_and_same('HDL_CSS_JS', HDL_CSS | HDL_JS);

    core_assert_const_and_same('HDL_ACT_CSS', HDL_ACT | HDL_CSS);
    core_assert_const_and_same('HDL_ACT_JS', HDL_ACT | HDL_JS);
    core_assert_const_and_same('HDL_ACT_CSS_JS', HDL_ACT | HDL_CSS | HDL_JS);

    core_assert_const_and_same('HDL_TPL_CSS', HDL_TPL | HDL_CSS);
    core_assert_const_and_same('HDL_TPL_JS', HDL_TPL | HDL_JS);
    core_assert_const_and_same('HDL_TPL_CSS_JS', HDL_TPL | HDL_CSS | HDL_JS);

    core_assert_const_and_same('HDL_ACT_TPL', HDL_ACT | HDL_TPL);
    core_assert_const_and_same('HDL_ACT_TPL_CSS', HDL_ACT | HDL_TPL | HDL_CSS);
    core_assert_const_and_same('HDL_ACT_TPL_JS', HDL_ACT | HDL_TPL | HDL_JS);
    core_assert_const_and_same('HDL_ACT_TPL_CSS_JS', HDL_ACT | HDL_TPL | HDL_CSS | HDL_JS);

    core_assert_const_and_same('HDL_ALL', HDL_ACT | HDL_TPL | HDL_CSS | HDL_JS);

    core_assert_const_and_same('DEF_HDL_FNAME', 'default');
    core_assert_const_and_same('COM_INI_FNAME', '__init__.php');
    core_assert_const_and_same('COM_INF_FNAME', '__info__.php');
    core_assert_const_and_same('DEF_API_FNAME', 'api.main.php');

    core_assert_const_and_same('HDL_TRY_DEF', 32);
    core_assert_const_and_same('HDL_ASC_DEF', 64);
}


/**
 * CREATE TESTING ENVIRONMENT
 * --------------------------
 */

function build_environment_test()
{
    store_system_registry();  # store system registry

    # clean up system registry
    sys_delete('route.stack');
    sys_delete('lng.loaded');
    sys_delete('inf.loaded');
    sys_delete('ini.loaded');
    sys_delete('api.loaded');
    sys_delete('hdl.loaded');
    sys_delete('hdl.preload.css');
    sys_delete('hdl.preload.js');

    set_route_root(TEMP_ROOT.'/test-response-lib', true);  # store and switch core root
    _build_test_tree();
}


/**
 * ROUTER TESTS
 * ------------
 */

# Legend:
# function naming: cell_nX_dY_default_test(), where:
# nX - test FS column number (n - number)
# dY - test FS row number (d - depth)
# default - core_use_handler() parameters set by default

# Checking order: inf, ini, api, hdl, css, js

## Root level

function cell_n0_d0_default_test()  # Top level
{
    store_system_registry();
    core_use_handler('');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

## Level 1 (component level)

function cell_n1_d1_default_test()
{
    store_system_registry();
    core_use_handler('d11');
    # inf
    core_assert_equal(sys_opt_count('inf.loaded'), 3);
    core_assert_equal(sys_opt_get('inf.loaded', 0), get_route_root().'/d11/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 1), get_route_root().'/d21/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 2), get_route_root().'/d31/__info__.php');
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 3);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 1), get_route_root().'/d11/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 2), get_route_root().'/d21/__init__.php');
    # api
    core_assert_equal(sys_opt_count('api.loaded'), 2);
    core_assert_equal(sys_opt_get('api.loaded', 0), get_route_root().'/d11/api.main.php');
    core_assert_equal(sys_opt_get('api.loaded', 1), get_route_root().'/d21/api.main.php');
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/d11/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/d11/tpl.default.php');
    # hdl.preload.css
    core_assert_false(sys_has_key('hdl.preload.css'));
    # hdl.preload.js
    core_assert_false(sys_has_key('hdl.preload.js'));
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n1_d1_css_nodef_noasc_test()
{
    store_system_registry();
    core_use_handler('d11.test11', empty_array(), HDL_CSS);
    core_assert_false(sys_has_key('inf.loaded'));
    core_assert_false(sys_has_key('ini.loaded'));
    core_assert_false(sys_has_key('api.loaded'));
    core_assert_false(sys_has_key('hdl.loaded'));
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/d11/web/s/test11.css');
    # hdl.preload.js
    core_assert_false(sys_has_key('hdl.preload.js'));
    //ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n2_d1_default_test()
{
    store_system_registry();
    core_use_handler('d21');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n2_d1_noasc_test()
{
    store_system_registry();
    core_use_handler('d21', empty_array(), HDL_TPL|HDL_TRY_DEF);
    core_assert_false(sys_has_key('inf.loaded'));
    core_assert_false(sys_has_key('ini.loaded'));
    core_assert_false(sys_has_key('api.loaded'));
    core_assert_false(sys_has_key('hdl.loaded'));
    core_assert_false(sys_has_key('hdl.preload.css'));
    core_assert_false(sys_has_key('hdl.preload.js'));
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n3_d1_default_test()
{
    store_system_registry();
    core_use_handler('d31');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n3_d1_noasc_test()
{
    store_system_registry();
    core_use_handler('d31', empty_array(), HDL_TPL|HDL_TRY_DEF);
    core_assert_false(sys_has_key('inf.loaded'));
    core_assert_false(sys_has_key('ini.loaded'));
    core_assert_false(sys_has_key('api.loaded'));
    core_assert_false(sys_has_key('hdl.loaded'));
    core_assert_false(sys_has_key('hdl.preload.css'));
    core_assert_false(sys_has_key('hdl.preload.js'));
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

## Level 2

function cell_n1_d2_default_test()
{
    store_system_registry();
    core_use_handler('d11.d12');
    # inf
    core_assert_equal(sys_opt_count('inf.loaded'), 3);
    core_assert_equal(sys_opt_get('inf.loaded', 0), get_route_root().'/d11/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 1), get_route_root().'/d21/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 2), get_route_root().'/d31/__info__.php');
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 3);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 1), get_route_root().'/d11/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 2), get_route_root().'/d21/__init__.php');
    # api
    core_assert_equal(sys_opt_count('api.loaded'), 2);
    core_assert_equal(sys_opt_get('api.loaded', 0), get_route_root().'/d11/api.main.php');
    core_assert_equal(sys_opt_get('api.loaded', 1), get_route_root().'/d21/api.main.php');
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 5);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/d11/d12/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/act.header.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 2), get_route_root().'/tpl.header.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 3), get_route_root().'/tpl.footer.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 4), get_route_root().'/d11/d12/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 2);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/header.css');
    core_assert_equal(sys_opt_get('hdl.preload.css', 1), TEST_URL_ROOT.'/web/s/footer.css');
    # hdl.preload.js
    core_assert_false(sys_has_key('hdl.preload.js'));
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n2_d2_default_test()
{
    store_system_registry();
    core_use_handler('d21.d22');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n2_d2_js_nodef_noasc_test()
{
    store_system_registry();
    core_use_handler('d21.d22.test22', empty_array(), HDL_JS);
    core_assert_false(sys_has_key('inf.loaded'));
    core_assert_false(sys_has_key('ini.loaded'));
    core_assert_false(sys_has_key('api.loaded'));
    core_assert_false(sys_has_key('hdl.loaded'));
    # hdl.preload.css
    core_assert_false(sys_has_key('hdl.preload.css'));
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/d21/d22/web/s/test22.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n3_d2_default_test()
{
    store_system_registry();
    core_use_handler('d31.d32');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

## Level 3

function cell_n1_d3_default_test()
{
    store_system_registry();
    core_use_handler('d11.d12.d13');
    # inf
    core_assert_equal(sys_opt_count('inf.loaded'), 3);
    core_assert_equal(sys_opt_get('inf.loaded', 0), get_route_root().'/d11/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 1), get_route_root().'/d21/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 2), get_route_root().'/d31/__info__.php');
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 3);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 1), get_route_root().'/d11/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 2), get_route_root().'/d21/__init__.php');
    # api
    core_assert_equal(sys_opt_count('api.loaded'), 2);
    core_assert_equal(sys_opt_get('api.loaded', 0), get_route_root().'/d11/api.main.php');
    core_assert_equal(sys_opt_get('api.loaded', 1), get_route_root().'/d21/api.main.php');
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/d11/d12/d13/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/d11/d12/d13/tpl.default.php');
    # hdl.preload.css
    core_assert_false(sys_has_key('hdl.preload.css'));
    # hdl.preload.js
    core_assert_false(sys_has_key('hdl.preload.js'));
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n2_d3_default_test()
{
    store_system_registry();
    core_use_handler('d21.d22.d23');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n3_d3_default_test()
{
    store_system_registry();
    core_use_handler('d31.d32.d33');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n3_d3_cssjs_nodef_noasc_test()
{
    store_system_registry();
    core_use_handler('d31.d32.d33.test33', empty_array(), HDL_CSS_JS);
    core_assert_false(sys_has_key('inf.loaded'));
    core_assert_false(sys_has_key('ini.loaded'));
    core_assert_false(sys_has_key('api.loaded'));
    core_assert_false(sys_has_key('hdl.loaded'));
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/d31/d32/d33/web/s/test33.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/d31/d32/d33/web/s/test33.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

## Level 4

function cell_n1_d4_default_test()
{
    store_system_registry();
    core_use_handler('d11.d12.d13.d14');
    # inf
    core_assert_equal(sys_opt_count('inf.loaded'), 3);
    core_assert_equal(sys_opt_get('inf.loaded', 0), get_route_root().'/d11/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 1), get_route_root().'/d21/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 2), get_route_root().'/d31/__info__.php');
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 3);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 1), get_route_root().'/d11/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 2), get_route_root().'/d21/__init__.php');
    # api
    core_assert_equal(sys_opt_count('api.loaded'), 2);
    core_assert_equal(sys_opt_get('api.loaded', 0), get_route_root().'/d11/api.main.php');
    core_assert_equal(sys_opt_get('api.loaded', 1), get_route_root().'/d21/api.main.php');
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/d11/d12/d13/d14/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/d11/d12/d13/d14/tpl.default.php');
    # hdl.preload.css
    core_assert_false(sys_has_key('hdl.preload.css'));
    # hdl.preload.js
    core_assert_false(sys_has_key('hdl.preload.js'));
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n2_d4_default_test()
{
    store_system_registry();
    core_use_handler('d21.d22.d23.d24');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n3_d4_default_test()
{
    store_system_registry();
    core_use_handler('d31.d32.d33.d34');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

## Level 5

function cell_n1_d5_default_test()
{
    store_system_registry();
    core_use_handler('d11.d12.d13.d14.d15');
    # inf
    core_assert_equal(sys_opt_count('inf.loaded'), 3);
    core_assert_equal(sys_opt_get('inf.loaded', 0), get_route_root().'/d11/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 1), get_route_root().'/d21/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 2), get_route_root().'/d31/__info__.php');
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 3);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 1), get_route_root().'/d11/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 2), get_route_root().'/d21/__init__.php');
    # api
    core_assert_equal(sys_opt_count('api.loaded'), 2);
    core_assert_equal(sys_opt_get('api.loaded', 0), get_route_root().'/d11/api.main.php');
    core_assert_equal(sys_opt_get('api.loaded', 1), get_route_root().'/d21/api.main.php');
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/d11/d12/d13/d14/d15/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/d11/d12/d13/d14/d15/tpl.default.php');
    # hdl.preload.css
    core_assert_false(sys_has_key('hdl.preload.css'));
    # hdl.preload.js
    core_assert_false(sys_has_key('hdl.preload.js'));
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n2_d5_default_test()
{
    store_system_registry();
    core_use_handler('d21.d22.d23.d24.d25');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n2_d5_test25_test()
{
    store_system_registry();
    core_use_handler('d21.d22.d23.d24.d25.test25');
    # inf
    core_assert_equal(sys_opt_count('inf.loaded'), 3);
    core_assert_equal(sys_opt_get('inf.loaded', 0), get_route_root().'/d21/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 1), get_route_root().'/d11/__info__.php');
    core_assert_equal(sys_opt_get('inf.loaded', 2), get_route_root().'/d31/__info__.php');
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 3);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 1), get_route_root().'/d21/__init__.php');
    core_assert_equal(sys_opt_get('ini.loaded', 2), get_route_root().'/d11/__init__.php');
    # api
    core_assert_equal(sys_opt_count('api.loaded'), 2);
    core_assert_equal(sys_opt_get('api.loaded', 0), get_route_root().'/d21/api.main.php');
    core_assert_equal(sys_opt_get('api.loaded', 1), get_route_root().'/d11/api.main.php');
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 6);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/d11/d12/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/act.header.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 2), get_route_root().'/tpl.header.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 3), get_route_root().'/tpl.footer.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 4), get_route_root().'/d11/d12/tpl.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 5), get_route_root().'/d21/d22/d23/d24/d25/tpl.test25.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 2);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/header.css');
    core_assert_equal(sys_opt_get('hdl.preload.css', 1), TEST_URL_ROOT.'/web/s/footer.css');
    # hdl.preload.js
    core_assert_false(sys_has_key('hdl.preload.js'));
    //ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}

function cell_n3_d5_default_test()
{
    store_system_registry();
    core_use_handler('d31.d32.d33.d34.d35');
    # inf
    core_assert_false(sys_has_key('inf.loaded'));
    # ini
    core_assert_equal(sys_opt_count('ini.loaded'), 1);
    core_assert_equal(sys_opt_get('ini.loaded', 0), get_route_root().'/__init__.php');
    # api
    core_assert_false(sys_has_key('api.loaded'));
    # hdl.loaded
    core_assert_equal(sys_opt_count('hdl.loaded'), 2);
    core_assert_equal(sys_opt_get('hdl.loaded', 0), get_route_root().'/act.default.php');
    core_assert_equal(sys_opt_get('hdl.loaded', 1), get_route_root().'/tpl.default.php');
    # hdl.preload.css
    core_assert_equal(sys_opt_count('hdl.preload.css'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.css', 0), TEST_URL_ROOT.'/web/s/default.css');
    # hdl.preload.js
    core_assert_equal(sys_opt_count('hdl.preload.js'), 1);
    core_assert_equal(sys_opt_get('hdl.preload.js', 0), TEST_URL_ROOT.'/web/s/default.js');
    #ndump('$SYS', $GLOBALS['SYS']);
    restore_system_registry();
}


/**
 * DESTROY TESTING ENVIRONMENT
 * ---------------------------
 */

function destroy_environment_test()
{
    _remove_test_tree();
    restore_route_root();  # restore core root
    restore_system_registry();  # restore system registry
}

