<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* URL helpers */

require_once('inc.dump.php');

#
# Actions

if (isset($_REQUEST['cookies-reset']))
{
    $past = time() - 3600;
    foreach($_COOKIE as $key => $value)
    {
        if ($key == 'PHPSESSID') continue;  # skip session cookie
        setcookie($key, $value, $past, '/');
    }
}
if (isset($_REQUEST['session-reset'])) $_SESSION = array();

#
# Calculations

if (isset($_REQUEST['crc32'])) ndump('crc32()', crc32($_REQUEST['crc32']));
if (isset($_REQUEST['md5'])) ndump('md5()', md5($_REQUEST['md5']));
if (isset($_REQUEST['sha1'])) ndump('sha1()', sha1($_REQUEST['sha1']));
if (isset($_REQUEST['hash'])) ndump('password_hash($pwd)', password_hash($_REQUEST['hash'], PASSWORD_DEFAULT));

#
# Output

if (isset($_REQUEST['dump-classes'])) ndump('CLASSES', get_declared_classes());
if (isset($_REQUEST['dump-constants'])
 or isset($_REQUEST['dump-consts'])) ndump('USER_CONSTANTS', get_defined_constants(true)['user']);
if (isset($_REQUEST['dump-cookies'])) ndump('$_COOKIE', $_COOKIE);
if (isset($_REQUEST['dump-functions'])) ndump('USER_FUNCTIONS', get_defined_functions()['user']);
if (isset($_REQUEST['dump-get'])) ndump('$_GET', $_GET);
if (isset($_REQUEST['dump-globals'])) ndump('$GLOBALS', $GLOBALS);
if (isset($_REQUEST['dump-headers'])) ndump('getallheaders()', getallheaders());
if (isset($_REQUEST['dump-interfaces'])) ndump('INTERFACES', get_declared_interfaces());
if (isset($_REQUEST['dump-ini'])) ndump('ini_get_all()', ini_get_all());
if (isset($_REQUEST['dump-post'])) ndump('$_POST', $_POST);
if (isset($_REQUEST['dump-request'])) ndump('$_REQUEST', $_REQUEST);
if (isset($_REQUEST['dump-server'])) ndump('$_SERVER', $_SERVER);
if (isset($_REQUEST['dump-session'])) ndump('$_SESSION', $_SESSION);
if (isset($_REQUEST['dump-sys'])
 or isset($_REQUEST['dump-system'])) ndump('$SYS', $SYS);
if (isset($_REQUEST['dump-variables'])
 or isset($_REQUEST['dump-vars'])) ndump('VARIABLES', get_defined_vars());

if (isset($_REQUEST['phpinfo'])) phpinfo();

