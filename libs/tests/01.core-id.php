<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Test core ID */


function core_id_test()
{
    core_assert_const('FRAMEWORK');
    core_assert_string(FRAMEWORK);

    core_assert_const('CORE_NAME');
    core_assert_string(CORE_NAME);

    core_assert_const('CORE_VERSION');
    core_assert_string(CORE_VERSION);
}
