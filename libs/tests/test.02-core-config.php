<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Test core config */


function _np($path) {
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}


function core_multilanguage_test()
{
    core_assert_const('ML_URL_SUPPORT');
    core_assert_bool(ML_URL_SUPPORT);

    core_assert_const('ML_DIR_SUPPORT');
    core_assert_bool(ML_DIR_SUPPORT);
}

function core_server_type_test()
{
    core_assert_const('IS_CRON');
    core_assert_bool(IS_CRON);

    core_assert_const('IS_LOCAL');
    core_assert_bool(IS_LOCAL);

    core_assert_const('IS_DEV');
    core_assert_bool(IS_DEV);

    core_assert_const('IS_PROD');
    core_assert_bool(IS_PROD);

    core_assert_same((int)IS_LOCAL + (int)IS_DEV + (int)IS_PROD, 1);

    core_assert_const('SERVER_TYPE');
    core_assert_string(SERVER_TYPE);
    core_assert_not_empty(SERVER_TYPE);
}

function core_paths_and_urls_test()
{
    core_assert_const('TIME_HASH');
    core_assert_string(TIME_HASH);
    core_assert_not_empty(TIME_HASH);

    core_assert_const('PROJ_ROOT');
    core_assert_string(PROJ_ROOT);
    core_assert_not_empty(PROJ_ROOT);
    core_assert_dir(PROJ_ROOT);

    # components

    core_assert_const('COMS_PATH');
    core_assert_string(COMS_PATH);
    core_assert_not_empty(COMS_PATH);
    core_assert_true(
        str_starts_with(_np(COMS_PATH),
        DIRECTORY_SEPARATOR)
    );

    core_assert_const('COMS_ROOT');
    core_assert_string(COMS_ROOT);
    core_assert_not_empty(COMS_ROOT);
    core_assert_dir(COMS_ROOT);

    core_assert_same(_np(COMS_ROOT), _np(PROJ_ROOT.COMS_PATH));

    # logs

    core_assert_const('LOGS_PATH');
    core_assert_string(LOGS_PATH);
    core_assert_not_empty(LOGS_PATH);
    core_assert_true(
        str_starts_with(_np(LOGS_PATH),
        DIRECTORY_SEPARATOR)
    );

    core_assert_const('LOGS_ROOT');
    core_assert_string(LOGS_ROOT);
    core_assert_not_empty(LOGS_ROOT);
    core_assert_dir(LOGS_ROOT);

    core_assert_same(_np(LOGS_ROOT), _np(PROJ_ROOT.LOGS_PATH));

    # temp

    core_assert_const('TEMP_PATH');
    core_assert_string(TEMP_PATH);
    core_assert_not_empty(TEMP_PATH);
    core_assert_true(
        str_starts_with(_np(TEMP_PATH),
        DIRECTORY_SEPARATOR)
    );

    core_assert_const('TEMP_ROOT');
    core_assert_string(TEMP_ROOT);
    core_assert_not_empty(TEMP_ROOT);
    core_assert_dir(TEMP_ROOT);

    core_assert_same(_np(TEMP_ROOT), _np(PROJ_ROOT.TEMP_PATH));
}

function core_php_settings_test()
{
    # security

    core_assert_false((bool)ini_get('allow_url_include'));
    core_assert_false((bool)ini_get('magic_quotes_gpc'));
    core_assert_false((bool)ini_get('register_globals'));

    # session

    core_assert_true((bool)ini_get('session.use_strict_mode'));
    core_assert_true((bool)ini_get('session.cookie_httponly'));

    $cookie_secure = (bool)ini_get('session.cookie_secure');
    if (IS_DEV || IS_PROD) core_assert_true($cookie_secure);

    core_assert_const('INI_SESSION_LIFETIME');
    core_assert_int(INI_SESSION_LIFETIME);
    core_assert_gt(INI_SESSION_LIFETIME, 0);

    core_assert_same(
        (int)ini_get('session.cookie_lifetime'),
        (int)INI_SESSION_LIFETIME
    );

    core_assert_false((bool)ini_get('session.auto_start'));
    core_assert_true((bool)ini_get('session.use_cookies'));

    # logging

    if (IS_PROD)  # production must log silently
    {
        core_assert_false((bool)ini_get('display_errors'));
        core_assert_false((bool)ini_get('display_startup_errors'));
    }
    core_assert_same(
        _np(INI_LOG_FILE),
        _np(ini_get('error_log'))
    );
    core_assert_gt((int)ini_get('error_reporting'), 0);
    core_assert_true((bool)ini_get('log_errors'));

    # encoding

    core_assert_const('INI_ENCODING');
    core_assert_string(INI_ENCODING);
    core_assert_not_empty(INI_ENCODING);

    core_assert_const('INI_LANGUAGE');
    core_assert_string(INI_LANGUAGE);
    core_assert_not_empty(INI_LANGUAGE);

    core_assert_same(INI_ENCODING, mb_internal_encoding());
    core_assert_same(INI_ENCODING, mb_http_output());
    core_assert_same(INI_ENCODING, mb_regex_encoding());
    core_assert_same(INI_LANGUAGE, mb_language());

    if (!IS_CRON)  # HTTP-mode only
    {
        $header = preg_filter('/^Content-Type:\s*(.*)$/i', '$1', headers_list());
        if ($header) {
            $ct = array_values($header)[0];
            core_assert_true(stripos($ct, 'charset='.INI_ENCODING) !== false);
        }
    }
}

function core_vip_mode_test()
{
    core_assert_const('VIP_MODE_KEY');
    core_assert_string(VIP_MODE_KEY);
    core_assert_not_empty(VIP_MODE_KEY);

    core_assert_const('IS_VIP');
    core_assert_bool(IS_VIP);
}

