<?php

/**
 * Provides acceptance(ish) tests for API functions.
 */
abstract class FetchPageTestCase extends TWFY_Database_TestCase {
    protected function base_fetch_page($vars, $dir, $page = 'index.php', $req_uri = '') {
        foreach ($vars as $k => $v) {
            $vars[$k] = $k . '=' . urlencode($v);
        }

        if (!$req_uri) {
            $req_uri = "/$dir/$page";
        }

        $vars = join('&', $vars);
        $command = 'parse_str($argv[1], $_GET); include_once("tests/Bootstrap.php"); chdir("www/docs/' . $dir . '"); include_once("' . $page . '");';
        $page = `REQUEST_METHOD=GET REQUEST_URI=$req_uri REMOTE_ADDR=127.0.0.1 php -e -r '$command' -- '$vars'`;

        return $page;
    }

    protected function base_fetch_page_user($vars, $cookie, $dir, $page = 'index.php', $req_uri = '') {
        foreach ($vars as $k => $v) {
            $vars[$k] = $k . '=' . urlencode($v);
        }

        if (!$req_uri) {
            $req_uri = "/$dir/$page";
        }

        $vars = join('&', $vars);
        $command = 'parse_str($argv[1], $_GET); $_COOKIE["epuser_id"] = "' . $cookie . '"; include_once("tests/Bootstrap.php"); chdir("www/docs/' . $dir . '"); include_once("' . $page . '");';
        $page = `REQUEST_METHOD=GET REQUEST_URI=$req_uri REMOTE_ADDR=127.0.0.1 php -e -r '$command' -- '$vars'`;

        return $page;
    }

    protected function base_post_page_user($vars, $cookie, $dir, $page = 'index.php', $req_uri = '') {
        foreach ($vars as $k => $v) {
            $vars[$k] = $k . '=' . urlencode($v);
        }

        if (!$req_uri) {
            $req_uri = "/$dir/$page";
        }

        $vars = join('&', $vars);
        $command = '
parse_str($argv[1], $_POST);
$_POST["_csrf_token_645a83a41868941e4692aa31e7235f2"] = "CSRF";
$_COOKIE["epuser_id"] = "' . $cookie . '";
session_start(); $_SESSION["_csrf_token_645a83a41868941e4692aa31e7235f2"] = "CSRF";
include_once("tests/Bootstrap.php");
chdir("www/docs/' . $dir . '");
include_once("' . $page . '");
';
        $page = `REQUEST_METHOD=POST REQUEST_URI=$req_uri REMOTE_ADDR=127.0.0.1 php -e -r '$command' -- '$vars'`;

        return $page;
    }
}
