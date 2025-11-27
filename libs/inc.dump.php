<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;
/* Dump API */


function _formatted_backtrace($backtrace)
{
    if (!is_array($backtrace)) {
        trigger_error('Invalid backtrace data format!', E_USER_ERROR);
    }
    $s = '';
    foreach($backtrace as $btrec)
    {
        if (!isset($btrec['file'], $btrec['line'])
         or $btrec['file'] == __FILE__) continue;
        $s .= 'Call from: '.$btrec['file'].':'.$btrec['line'].PHP_EOL;
    }
    return $s;
}

function _core_dump(...$values)
{
    if (count($values) === 0)
    {
        trigger_error('Nothing to dump!', E_USER_NOTICE);
        return false;
    }
    $s  = "<pre class='dump default'>\n";
    foreach($values as $value)
    {
        $s .= htmlentities(@var_export($value, true), ENT_QUOTES);
    }
    $s .= "</pre>\n";
    $s .= "<!--\n";
    $s .= _formatted_backtrace(debug_backtrace());
    $s .= "-->\n";
    print $s;
}

function dump(...$values) { _core_dump(...$values); }

function ndump($name, $value)
{
    $s  = "<pre class='dump ndump'>\n";
    $s .= strval($name).' = ';
    $s .= htmlentities(@var_export($value, true), ENT_QUOTES);
    $s .= "</pre>\n";
    $s .= "<!--\n";
    $s .= _formatted_backtrace(debug_backtrace());
    $s .= "-->\n";
    print $s;
}

function tdump(...$values)
{
    print("<table class='dump tdump' border='1' cellpadding='5' cellspacing='2'>\n\t<tr>\n");
    foreach($values as $index => $value)
    {
        print("\t\t<td valign='top'>\n");
        print(_core_dump($value));
        print("\t\t</td>\n");
    }
    print("\t</tr>\n</table>\n");
}

function dump_js_log(...$values)
{
    if (count($values) === 0) return;
    $result = array();
    foreach($values as $value)
    {
        $exported = var_export($value, true);
        $sanitized = str_replace(array("\r\n", "\n", "\r"), '\n', $exported);
        $result[] = $sanitized;
    }
    print('<script>console.log("'.implode('","', $result).'");</script>'.PHP_EOL);
}

