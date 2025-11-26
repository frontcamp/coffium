
<table>
    <thead>
        <tr>
            <th>Parameter</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr><td colspan="2">System</td></tr>

        <tr><td>FRAMEWORK</td><td><?=FRAMEWORK?></td></tr>
        <tr><td>CORE_NAME</td><td><?=CORE_NAME?></td></tr>
        <tr><td>CORE_VERSION</td><td><?=CORE_VERSION?></td></tr>

        <tr><td colspan="2">Multilingual support</td></tr>

        <tr><td>ML_URL_SUPPORT</td><td><?=bool_to_onoff(ML_URL_SUPPORT)?></td></tr>
        <tr><td>ML_DIR_SUPPORT</td><td><?=bool_to_onoff(ML_DIR_SUPPORT)?></td></tr>

        <tr><td colspan="2">Server type</td></tr>

        <tr><td>IS_CRON</td><td><?=bool_to_yesno(IS_CRON)?></td></tr>
        <tr><td>IS_LOCAL</td><td><?=bool_to_yesno(IS_LOCAL)?></td></tr>
        <tr><td>IS_DEV</td><td><?=bool_to_yesno(IS_DEV)?></td></tr>
        <tr><td>IS_PROD</td><td><?=bool_to_yesno(IS_PROD)?></td></tr>
        <tr><td>SERVER_TYPE</td><td><?=SERVER_TYPE?></td></tr>

        <tr><td colspan="2">Paths</td></tr>

        <tr><td>TIME_HASH</td><td><?=TIME_HASH?></td></tr>
        <tr><td>PROJ_ROOT</td><td><?=PROJ_ROOT?></td></tr>
        <tr><td>COMS_PATH</td><td><?=COMS_PATH?></td></tr>
        <tr><td>COMS_ROOT</td><td><?=COMS_ROOT?></td></tr>
        <tr><td>LOGS_PATH</td><td><?=LOGS_PATH?></td></tr>
        <tr><td>LOGS_ROOT</td><td><?=LOGS_ROOT?></td></tr>
        <tr><td>TEMP_PATH</td><td><?=TEMP_PATH?></td></tr>
        <tr><td>TEMP_ROOT</td><td><?=TEMP_ROOT?></td></tr>

        <tr><td colspan="2">PHP settings (overrided)</td></tr>

        <tr><td>date_default_timezone_get()</td><td><?=date_default_timezone_get()?></td></tr>
        <tr><td>allow_url_include</td><td><?=some_to_onoff(ini_get('allow_url_include'))?></td></tr>
        <tr><td>magic_quotes_gpc</td><td><?=some_to_onoff(ini_get('magic_quotes_gpc'))?></td></tr>
        <tr><td>register_globals</td><td><?=some_to_onoff(ini_get('register_globals'))?></td></tr>
        <tr><td>zlib.output_compression</td><td><?=some_to_onoff(ini_get('zlib.output_compression'))?></td></tr>

        <tr><td colspan="2">Session</td></tr>

        <tr><td>session.use_strict_mode</td><td><?=some_to_onoff(ini_get('session.use_strict_mode'))?></td></tr>
        <tr><td>session.cookie_httponly</td><td><?=some_to_onoff(ini_get('session.cookie_httponly'))?></td></tr>
        <tr><td>session.cookie_secure</td><td><?=some_to_onoff(ini_get('session.cookie_secure'))?></td></tr>
        <tr><td>PHP_SESSION_LIFETIME</td><td><?=PHP_SESSION_LIFETIME?></td></tr>
        <tr><td>session.auto_start</td><td><?=some_to_onoff(ini_get('session.auto_start'))?></td></tr>
        <tr><td>session.cookie_lifetime</td><td><?=ini_get('session.cookie_lifetime')?></td></tr>
        <tr><td>session.use_cookies</td><td><?=some_to_onoff(ini_get('session.use_cookies'))?></td></tr>
        <tr><td>session.name</td><td><?=ini_get('session.name')?></td></tr>
        <tr><td>session_id()</td><td><?=session_id()?></td></tr>

        <tr><td colspan="2">Logging</td></tr>

        <tr><td>display_errors</td><td><?=some_to_onoff(ini_get('display_errors'))?></td></tr>
        <tr><td>display_startup_errors</td><td><?=some_to_onoff(ini_get('display_startup_errors'))?></td></tr>
        <tr><td>error_log</td><td><?=ini_get('error_log')?></td></tr>
        <tr><td>error_reporting</td><td><?=ini_get('error_reporting')?></td></tr>
        <tr><td>log_errors</td><td><?=some_to_onoff(ini_get('log_errors'))?></td></tr>

        <tr><td colspan="2">Encoding</td></tr>

<?
$header  = preg_filter('/^Content-Type:\s*(.*)$/i', '$1', headers_list());
$cttype = $header ? array_values($header)[0] : '(not set)';
$CONTENT_TYPE = htmlspecialchars($cttype, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
?>

        <tr><td>Content-Type</td><td><?=$CONTENT_TYPE?></td></tr>
        <tr><td>mb_internal_encoding()</td><td><?=mb_internal_encoding()?></td></tr>
        <tr><td>mb_http_output()</td><td><?=mb_http_output()?></td></tr>
        <tr><td>mb_language()</td><td><?=mb_language()?></td></tr>
        <tr><td>mb_regex_encoding()</td><td><?=mb_regex_encoding()?></td></tr>

        <tr><td colspan="2">VIP access</td></tr>

        <tr><td>IS_VIP</td><td><?=bool_to_yesno(IS_VIP)?></td></tr>
        <tr><td>VIP_MODE_KEY</td><td><?=VIP_MODE_KEY?></td></tr>

    </tbody>
</table>

