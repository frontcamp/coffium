<?php
defined('INDEX') or die('Forbidden!');
$GLOBALS['SYS']['included'][] = __FILE__;

$COM_ALIASES = core_com_list();

$COMPONENTS = array();
foreach($COM_ALIASES as $alias)
{
    $COM_INFO = core_com_info($alias);
    $COM_INFO['require'] = $COM_INFO['require_str'];
    unset($COM_INFO['require_str']);
    $COMPONENTS[$alias] = $COM_INFO;
}

?>

<table width="100%" cellpadding="4" cellspacing="0" border="1">
    <tbody>
<? if (empty($COMPONENTS)): ?>
        <tr>
            <td colspan="2"><em>No components found.</em></td>
        </tr>
<? else: ?>
<?     foreach ($COMPONENTS as $alias => $com_info): ?>
            <tr>
                <th colspan="2">
                    <h2><?=htmlspecialchars($com_info['title'].' ('.$alias.')')?></h2>
                </th>
            </tr>
<?         foreach ($com_info as $key => $value): ?>
                <tr>
                    <td><?=htmlspecialchars((string)$key)?></td>
                    <td><?=htmlspecialchars((string)$value)?></td>
                </tr>
<?         endforeach ?>
<?     endforeach ?>
<? endif ?>
    </tbody>
</table>

