<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Test core config */


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

    core_assert_const('SERVER_TYPE');
    core_assert_string(SERVER_TYPE);
}





