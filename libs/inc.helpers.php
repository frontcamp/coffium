<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* URL helpers */

require_once('inc.dump.php');

#
# State / session actions

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
# Hashes & calculations

if (isset($_REQUEST['crc32'])) ndump('crc32()', crc32($_REQUEST['crc32']));
if (isset($_REQUEST['md5'])) ndump('md5()', md5($_REQUEST['md5']));
if (isset($_REQUEST['sha1'])) ndump('sha1()', sha1($_REQUEST['sha1']));
if (isset($_REQUEST['hash'])) ndump('password_hash($pwd)', password_hash($_REQUEST['hash'], PASSWORD_DEFAULT));

#
# PHP / system info

if (isset($_REQUEST['phpinfo'])) phpinfo();
if (isset($_REQUEST['dump-phpver'])) ndump('PHP_VERSION', PHP_VERSION.' ('.PHP_SAPI.')');
if (isset($_REQUEST['dump-sysinfo'])) include('inc.sysinfo.php');
if (isset($_REQUEST['dump-headers'])) ndump('getallheaders()', getallheaders());
if (isset($_REQUEST['dump-ini'])) ndump('ini_get_all()', ini_get_all());

#
# Superglobal arrays

if (isset($_REQUEST['dump-get'])) ndump('$_GET', $_GET);
if (isset($_REQUEST['dump-post'])) ndump('$_POST', $_POST);
if (isset($_REQUEST['dump-request'])) ndump('$_REQUEST', $_REQUEST);
if (isset($_REQUEST['dump-cookies'])) ndump('$_COOKIE', $_COOKIE);
if (isset($_REQUEST['dump-session'])) ndump('$_SESSION', $_SESSION);

#
# Environment & symbol table

if (isset($_REQUEST['dump-server'])) ndump('$_SERVER', $_SERVER);
if (isset($_REQUEST['dump-globals'])) ndump('$GLOBALS', $GLOBALS);
if (isset($_REQUEST['dump-classes'])) ndump('CLASSES', get_declared_classes());
if (isset($_REQUEST['dump-interfaces'])) ndump('INTERFACES', get_declared_interfaces());
if (isset($_REQUEST['dump-functions'])) ndump('USER_FUNCTIONS', get_defined_functions()['user']);
if (isset($_REQUEST['dump-constants'])
 || isset($_REQUEST['dump-consts'])) ndump('USER_CONSTANTS', get_defined_constants(true)['user']);
if (isset($_REQUEST['dump-variables'])
 || isset($_REQUEST['dump-vars'])) ndump('VARIABLES', get_defined_vars());

# Core inners

if (isset($_REQUEST['dump-sys'])
 || isset($_REQUEST['dump-system'])) ndump('$SYS', $SYS);
if (isset($_REQUEST['dump-finalizers']))
{
    # WARNING: finalizers snapshot is captured before execution,
    # so finalizers registered during finalization are not included

    global $_FINALIZERS_SNAPSHOT;

    $dump = array();

    if (!empty($_FINALIZERS_SNAPSHOT))
    {
        ksort($_FINALIZERS_SNAPSHOT);
        foreach ($_FINALIZERS_SNAPSHOT as $priority => $list)
        {
            foreach ($list as $callback)
            {
                $callable = '(unknown callable)';
                $file     = null;
                $line     = null;
                $ref      = null;

                if (is_string($callback))
                {
                    if (strpos($callback, '::') !== false)
                    {
                        list($class, $method) = explode('::', $callback, 2);
                        $callable = $class.'::'.$method;

                        if (class_exists($class) && method_exists($class, $method))
                        {
                            try { $ref = new ReflectionMethod($class, $method); } catch (ReflectionException $e) {}
                        }
                    }
                    else
                    {
                        $callable = $callback;

                        if (function_exists($callback))
                        {
                            try { $ref = new ReflectionFunction($callback); } catch (ReflectionException $e) {}
                        }
                    }
                }
                elseif ($callback instanceof Closure)
                {
                    $callable = 'closure';
                    try { $ref = new ReflectionFunction($callback); } catch (ReflectionException $e) {}
                }
                elseif (is_array($callback) && count($callback) === 2)
                {
                    $objectOrClass = $callback[0];
                    $method        = $callback[1];

                    $callable = (is_object($objectOrClass)
                        ? get_class($objectOrClass)
                        : $objectOrClass).'::'.$method;

                    try { $ref = new ReflectionMethod($objectOrClass, $method); } catch (ReflectionException $e) {}
                }

                if ($ref)
                {
                    $file = $ref->getFileName();
                    $line = $ref->getStartLine();
                }

                $s = $callable;
                if ($callable !== '(unknown callable)') {
                    $s .= '()';
                }
                if ($file !== null)
                {
                    $s .= ' in '.$file;
                    if ($line) $s .= ':'.$line;
                }

                $dump[$priority][] = $s;
            }
        }
    }

    ndump('FINALIZERS', $dump);
}

#
# Unit tests

if (isset($_REQUEST['core-test']))
{
    require_once('inc.test.php');
    launch_tests(PROJ_ROOT.'/libs/tests/');
}

