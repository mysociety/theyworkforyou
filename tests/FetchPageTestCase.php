<?php

/**
 * Provides acceptance(ish) tests for API functions.
 */
abstract class FetchPageTestCase extends TWFY_Database_TestCase
{

    protected function base_fetch_page($method, $vars = array(), $dir, $page = 'index.php', $ENV = '')
    {

        if ( $method ) {
            $vars['method'] = $method;
        }

        foreach ($vars as $k => $v) {
            $vars[$k] =  $k . '=' . urlencode($v);
        }

        $vars = join('&', $vars);
        $command = 'parse_str($argv[1], $_GET); include_once("tests/Bootstrap.php"); chdir("' . $dir . '"); include_once("' . $page . '");';
        $page = `$ENV REMOTE_ADDR=127.0.0.1 php -e -r '$command' -- '$vars'`;

        return $page;
    }

}
