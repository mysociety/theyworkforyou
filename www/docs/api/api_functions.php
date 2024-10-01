<?php

include_once INCLUDESPATH . '../../commonlib/phplib/rabx.php';

# The METHODS

$methods = [
    'getQuota' => [
        'parameters' => [],
        'required' => false,
        'help' => 'Return your current quota usage/limit (does not use up quota)',
    ],
    'getConstituency' => [
        'new' => true,
        'parameters' => ['name', 'postcode'],
        'required' => true,
        'help' => 'Searches for a UK Parliament constituency and returns details',
    ],
    'getConstituencies' => [
        'parameters' => ['date', 'search', 'latitude', 'longitude', 'distance'],
        'required' => false,
        'help' => 'Returns list of UK Parliament constituencies',
    ],
    'getPerson' => [
        'new' => true,
        'parameters' => ['id'],
        'required' => true,
        'help' => 'Returns main details for a person',
    ],
    'getMP' => [
        'parameters' => ['id', 'constituency', 'postcode', 'always_return', 'extra'],
        'required' => true,
        'help' => 'Returns main details for an MP',
    ],
    'getMPInfo' => [
        'parameters' => ['id', 'fields'],
        'required' => true,
        'help' => 'Returns extra information for a person',
    ],
    'getMPsInfo' => [
        'parameters' => ['id', 'fields'],
        'required' => true,
        'help' => 'Returns extra information for one or more people',
    ],
    'getMPs' => [
        'parameters' => ['party', 'date', 'search'],
        'required' => false,
        'help' => 'Returns list of MPs',
    ],
    'getLord' => [
        'parameters' => ['id'],
        'required' => true,
        'help' => 'Returns details for a Lord',
    ],
    'getLords' => [
        'parameters' => ['date', 'party', 'search'],
        'required' => false,
        'help' => 'Returns list of Lords',
    ],
    'getMLA' => [
        'parameters' => ['id', 'constituency', 'postcode', 'always_return'],
        'required' => true,
        'help' => 'Returns details for an MLA',
    ],
    'getMLAs' => [
        'parameters' => ['date', 'party', 'search'],
        'required' => false,
        'help' => 'Returns list of MLAs',
    ],
    'getMSP' => [
        'parameters' => ['id', 'constituency', 'postcode', 'always_return'],
        'required' => true,
        'help' => 'Returns details for an MSP',
    ],
    'getMSPs' => [
        'parameters' => ['date', 'party', 'search'],
        'required' => false,
        'help' => 'Returns list of MSPs',
    ],
    'getGeometry' => [
        'new' => true,
        'parameters' => ['name'],
        'required' => true,
        'help' => 'Returns centre, bounding box of UK Parliament constituencies',
    ],
    'getBoundary' => [
        'parameters' => ['name'],
        'required' => true,
        'help' => 'Returns boundary polygon of UK Parliament constituency',
    ],
    'getCommittee' => [
        'new' => true,
        'parameters' => ['name', 'date'],
        'required' => false,
        'help' => 'Returns members of Select Committee',
    ],
    'getDebates' => [
        'new' => true,
        'parameters' => ['type', 'date', 'search', 'person', 'gid', 'year', 'order', 'page', 'num'],
        'required' => true,
        'help' => 'Returns Debates (either Commons, Westminster Hall, or Lords)',
    ],
    'getWrans' => [
        'parameters' => ['date', 'search', 'person', 'gid', 'year', 'order', 'page', 'num'],
        'required' => true,
        'help' => 'Returns Written Answers',
    ],
    'getWMS' => [
        'parameters' => ['date', 'search', 'person', 'gid', 'year', 'order', 'page', 'num'],
        'required' => true,
        'help' => 'Returns Written Ministerial Statements',
    ],
    'getHansard' => [
        'parameters' => ['search', 'person', 'order', 'page', 'num'],
        'required' => true,
        'help' => 'Returns any of the above',
    ],
    'getComments' => [
        'new' => true,
        'parameters' => ['search', 'page', 'num', 'pid', 'start_date', 'end_date'],
        'required' => false,
        'help' => 'Returns comments',
    ],
    'convertURL' => [
        'new' => true,
        'parameters' => ['url'],
        'required' => true,
        'help' => 'Converts a parliament.uk Hansard URL into a TheyWorkForYou one, if possible',
    ],
    'getAlerts' => [
        'parameters' => ['start_date', 'end_date'],
        'required' => true,
        'superuser' => true,
        'help' => 'Returns a summary of email alert subscriptions created between two dates',
    ],
];

# Key-related functions

function api_log_call($key) {
    if ($key == 'DOCS') {
        return;
    }
    $ip = $_SERVER['REMOTE_ADDR'];
    $query = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $query = preg_replace('#key=[A-Za-z0-9]+&?#', '', $query);
    $db = new ParlDB();
    $db->query("INSERT INTO api_stats (api_key, ip_address, query_time, query)
        VALUES (:key, :ip, NOW(), :query)", [
        ':key' => $key,
        ':ip' => $ip,
        ':query' => $query,
    ]);
}

function api_is_superuser_key($key) {
    $db = new ParlDB();
    $q = $db->query('SELECT api_key.user_id, users.status
               FROM   api_key, users
               WHERE  users.user_id = api_key.user_id
               AND    api_key.api_key = :key', [
        ':key' => $key,
    ])->first();
    if (!$q) {
        return false;
    }
    if ($q['status'] == 'Superuser') {
        return true;
    }
    return false;
}

function api_check_key($key) {
    $db = new ParlDB();
    $q = $db->query('SELECT user_id, disabled FROM api_key WHERE api_key = :key', [
        ':key' => $key,
    ])->first();
    if (!$q) {
        return false;
    }
    if ($q['disabled']) {
        return 'disabled';
    }
    return true;
}

# Front-end sidebar of all methods

function api_sidebar($subscription) {
    global $methods;
    $sidebar = '';
    if ($subscription && $subscription->stripe) {
        $sidebar .= '<div class="block"><h4>Your account</h4><div class="blockbody"><ul>';
        $sidebar .= '<li><a href="/api/key">Plan and keys</a></li>';
        $sidebar .= '<li><a href="/api/invoices">Invoices</a></li>';
        $sidebar .= '</ul></div></div>';
    }
    $sidebar .= '<div class="block"><h4>API Functions</h4> <div class="blockbody"><ul>';
    foreach ($methods as $method => $data) {
        if (isset($data['superuser']) && $data['superuser']) {
            continue;
        }
        $style = isset($data['new']) ? ' style="border-top: solid 1px #999999;"' : '';
        $sidebar .= "<li$style>";
        $sidebar .= "<a href='" . WEBPATH . "api/docs/$method'>$method</a>";
        $sidebar .= "<br>$data[help]</li>";
    }
    $sidebar .= '</ul></div></div>';
    $sidebar = [
        'type' => 'html',
        'content' => $sidebar,
    ];
    return $sidebar;
}

# Output functions

function api_output($arr, $last_mod = null) {
    $output = get_http_var('output');
    if (!get_http_var('docs')) {
        $cond = api_header($output, $last_mod);
        if ($cond) {
            return;
        }
    }
    if ($output == 'xml') {
        $out = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $out .= '<twfy>' . api_output_xml($arr) . '</twfy>';
    } elseif ($output == 'php') {
        $out = api_output_php($arr);
    } elseif ($output == 'rabx') {
        $out = api_output_rabx($arr);
    } elseif ($output == 'json') {
        $out = json_encode($arr, JSON_PRETTY_PRINT);
    } else {
        # JS
        $out = api_output_js($arr);
        $callback = get_http_var('callback');
        if (preg_match('#^[A-Za-z0-9._[\]]+$#', $callback)) {
            $out = "$callback($out)";
        }
    }
    print $out;
}

function api_header($o, $last_mod = null) {
    if ($last_mod && array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
        $t = cond_parse_http_date($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        if (isset($t) && $t >= $last_mod) {
            header('HTTP/1.0 304 Not Modified');
            header('Last-Modified: ' . date('r', $last_mod));
            return true;
        }
    }
    $charset = 'utf-8';
    if ($o == 'xml') {
        $type = 'text/xml';
    } elseif ($o == 'php') {
        $type = 'text/php';
    } elseif ($o == 'rabx') {
        $type = 'application/octet-stream';
    } elseif ($o == 'json') {
        header('Access-Control-Allow-Origin: *');
        $type = 'application/json';
    } else {
        header('Access-Control-Allow-Origin: *');
        $charset = 'iso-8859-1';
        $type = 'text/javascript';
    }
    #$type = 'text/plain';
    header("Content-Type: $type; charset=$charset");
    if ($last_mod > 0) {
        header('Last-Modified: ' . date('r', $last_mod));
    }
    return false;
}

function api_error($e) {
    api_output(['error' => $e]);
}

function api_output_php($arr) {
    $out = serialize($arr);
    if (get_http_var('verbose')) {
        $out = str_replace(';', ";\n", $out);
    }
    return $out;
}

function api_output_rabx($arr) {
    $out = '';
    rabx_wire_wr($arr, $out);
    if (get_http_var('verbose')) {
        $out = str_replace(',', ",\n", $out);
    }
    return $out;
}

$api_xml_arr = 0;
function api_output_xml($v) {
    global $api_xml_arr;
    $verbose = get_http_var('verbose') ? "\n" : '';
    if (is_array($v)) {
        if (count($v) && array_keys($v) === range(0, count($v) - 1)) {
            $elt = 'match';
            $api_xml_arr++;
            $out = "<$elt>";
            $out .= join("</$elt>$verbose<$elt>", array_map('api_output_xml', $v));
            $out .= "</$elt>$verbose";
            return $out;
        }
        $out = '';
        foreach ($v as $k => $vv) {
            $out .= (is_numeric($k) || strpos($k, ' ')) ? '<match><id>' . _htmlspecialchars($k) . '</id>' : "<$k>";
            $out .= api_output_xml($vv);
            $out .= (is_numeric($k) || strpos($k, ' ')) ? '</match>' : "</$k>";
            $out .= $verbose;
        }
        return $out;
    } else {
        return _htmlspecialchars($v);
    }
}

function api_output_js($v, $level = 0) {
    $verbose = get_http_var('verbose') ? "\n" : '';
    $out = '';
    if (is_array($v)) {
        # PHP arrays are both JS arrays and objects
        if (count($v) && array_keys($v) === range(0, count($v) - 1)) {
            $out = '[' . join(",$verbose", array_map('api_output_js', $v)) . ']';
        } else {
            $out = '{' . $verbose;
            $b = false;
            foreach ($v as $k => $vv) {
                if ($b) {
                    $out .= ",$verbose";
                }
                if ($verbose) {
                    $out .= str_repeat(' ', ($level + 1) * 2);
                    $out .= '"' . $k . '" : ';
                } else {
                    $out .= '"' . $k . '":';
                }
                $out .= api_output_js($vv, $level + 1);
                $b = true;
            }
            if ($verbose) {
                $out .= "\n" . str_repeat(' ', $level * 2);
            }
            $out .= '}';
        }
    } elseif (is_null($v)) {
        $out = "null";
    } elseif (is_string($v)) {
        $out = '"' . str_replace(
            ["\\",'"',"\n","\t","\r", "‶", "″", "“", "”"],
            ["\\\\",'\"','\n','\t','\r', '\"', '\"', '\"', '\"'],
            $v
        ) . '"';
    } elseif (is_bool($v)) {
        $out = $v ? 'true' : 'false';
    } elseif (is_int($v) || is_float($v)) {
        $out = $v;
    }

    // we only want to convert to iso if it's an actual API call
    // so skip this if it's a documentation page
    if (!get_http_var('docs')) {
        // and then catch any errors in the conversion and just ignore
        // them and return the unconverted results
        $converted_out = @iconv('utf-8', 'iso-8859-1//TRANSLIT', $out);
        if ($converted_out !== false) {
            $out = $converted_out;
        }
    }

    return $out;
}

# Call an API function

function api_call_user_func_or_error($function, $params, $error, $type) {
    if (function_exists($function)) {
        call_user_func_array($function, $params);
    } elseif ($type == 'api') {
        api_error($error);
    } else {
        print "<p style='color:#cc0000'>$error</p>";
    }
}

# Used for testing for conditional responses

$cond_wkday_re = '(Sun|Mon|Tue|Wed|Thu|Fri|Sat)';
$cond_weekday_re = '(Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday)';
$cond_month_re = '(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)';
$cond_month_map = [
    'Jan' =>  1, 'Feb' =>  2, 'Mar' =>  3, 'Apr' =>  4,
    'May' =>  5, 'Jun' =>  6, 'Jul' =>  7, 'Aug' =>  8,
    'Sep' =>  9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12,
];

$cond_date1_re = '(\d\d) ' . $cond_month_re . ' (\d\d\d\d)';
$cond_date2_re = '(\d\d)-' . $cond_month_re . '-(\d\d)';
$cond_date3_re = $cond_month_re . ' (\d\d| \d)';

$cond_time_re = '([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]|6[012])';

function cond_parse_http_date($date) {
    $ma = [];
    global $cond_wkday_re, $cond_weekday_re, $cond_month_re, $cond_month_map,
    $cond_date1_re, $cond_date2_re, $cond_date3_re, $cond_time_re;
    if (preg_match("/^$cond_wkday_re, $cond_date1_re $cond_time_re GMT\$/", $date, $ma)) {
        /* RFC 1123 */
        $d = $ma[2];
        $m = $cond_month_map[$ma[3]];
        $Y = $ma[4];
        $H = $ma[5];
        $M = $ma[6];
        $S = $ma[7];
    } elseif (preg_match("/^$cond_weekday_re, $cond_date2_re $cond_time_re GMT\$/", $date, $ma)) {
        /* RFC 850 */
        $d = $ma[2];
        $m = $cond_month_map[$ma[3]];
        $Y = $ma[4] + ($ma[4] < 50 ? 2000 : 1900); /* XXX */
        $H = $ma[5];
        $M = $ma[6];
        $S = $ma[7];
    } elseif (preg_match("/^$cond_wkday_re $cond_date3_re $cond_time_re (\\d{4})\$/", $date, $ma)) {
        /* asctime(3) */
        $d = preg_replace('/ /', '', $ma[3]);
        $m = $cond_month_map[$ma[2]];
        $Y = $ma[7];
        $H = $ma[4];
        $M = $ma[5];
        $S = $ma[6];
    } else {
        return null;
    }

    return gmmktime($H, $M, $S, $m, $d, $Y);
}
