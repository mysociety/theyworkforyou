<?php

// Constants of types used in epobjects and hansard objects
// $Id: dbtypes.php,v 1.8 2009-04-28 13:13:04 matthew Exp $

// The type field in the epobject database table
define('EPTYPE_HANSARD', 1);
define('EPTYPE_GLOSSARY', 2);

// The major field in the hansard database table
$hansardmajors = [
    1 => [
        'type' => 'debate',
        'title' => "Commons debates",
        'singular' => 'debate',
        'plural' => 'debates',
        'page' => 'debate',
        'page_all' => 'debates',
        'page_year' => 'debatesyear',
        'sidebar' => 'hocdebates',
        'sidebar_short' => 'hocdebates_short',
        'location' => 'UK',
    ],
    2 => [
        'type' => 'debate',
        'title' => "Westminster Hall debates",
        'singular' => 'debate',
        'plural' => 'debates',
        'page' => 'whall',
        'page_all' => 'whalls',
        'page_year' => 'whallyear',
        'sidebar' => 'whalldebates',
        'sidebar_short' => 'whalldebates_short',
        'location' => 'UK',
    ],
    # Written answers have minor 1 for question, 2 for answer
    3 => [
        'type' => 'other',
        'title' => "Written Answers",
        'singular' => 'answer',
        'plural' => 'answers',
        'page' => 'wrans',
        'page_all' => 'wrans',
        'page_year' => 'wransyear',
        'sidebar' => 'wrans',
        'sidebar_short' => 'wrans_short',
        'location' => 'UK',
    ],
    4 => [
        'type' => 'other',
        'title' => "Written Ministerial Statements",
        'singular' => 'statement',
        'plural' => 'statements',
        'page' => 'wms',
        'page_all' => 'wms',
        'page_year' => 'wmsyear',
        'sidebar' => 'wms',
        'sidebar_short' => 'wms_short',
        'location' => 'UK',
    ],
    101 => [
        'type' => 'debate',
        'title' => 'Lords debates',
        'singular' => 'debate',
        'plural' => 'debates',
        'page' => 'lordsdebate',
        'page_all' => 'lordsdebates',
        'page_year' => 'lordsdebatesyear',
        'sidebar' => 'holdebates',
        'sidebar_short' => 'holdebates_short',
        'location' => 'UK',
    ],
    5 => [
        'type' => 'debate',
        'title' => 'Northern Ireland Assembly debates',
        'singular' => 'debate',
        'plural' => 'debates',
        'page' => 'nidebate',
        'page_all' => 'nidebates',
        'page_year' => 'nidebatesyear',
        'sidebar' => 'nidebates',
        'sidebar_short' => 'nidebates_short',
        'location' => 'NI',
    ],
    6 => [
        'type' => 'debate',
        'title' => 'Public Bill Committees',
        'singular' => 'clause',
        'plural' => 'clauses',
        'page' => 'pbc_speech',
        'page_all' => 'pbc_clause',
        # Committees never have a view by date/year, but used in replacement
        'page_year' => 'pbc_year',
        'sidebar' => 'pbc',
        'sidebar_short' => 'pbc_short',
        'location' => 'UK',
    ],
    7 => [
        'type' => 'debate',
        'title' => 'Scottish Parliament debates',
        'singular' => 'debate',
        'plural' => 'debates',
        'page' => 'spdebate',
        'page_all' => 'spdebates',
        'page_year' => 'spdebatesyear',
        'sidebar' => 'spdebates',
        'sidebar_short' => 'spdebates_short',
        'location' => 'Scotland',
    ],
    8 => [
        'type' => 'other',
        'title' => "Scottish Parliament written answers",
        'singular' => 'answer',
        'plural' => 'answers',
        'page' => 'spwrans',
        'page_all' => 'spwrans',
        'page_year' => 'spwransyear',
        'sidebar' => 'spwrans',
        'sidebar_short' => 'spwrans_short',
        'location' => 'Scotland',
    ],
    9 => [
        'type' => 'other',
        'title' => "Questions to the Mayor of London",
        'singular' => 'answer',
        'plural' => 'answers',
        'page' => 'lmqs',
        'page_all' => 'lmqs',
        'page_year' => 'lmqsyear',
        'sidebar' => 'lmqs',
        'sidebar_short' => 'lmqs_short',
        'location' => 'London',
    ],
    10 => [
        'type' => 'debate',
        'title' => 'Welsh Parliament record',
        'singular' => gettext('debate'),
        'plural' => gettext('debates'),
        'page' => 'senedddebate',
        'page_all' => 'senedddebates',
        'page_year' => 'senedddebatesyear',
        'sidebar' => 'senedddebates',
        'sidebar_short' => 'senedddebates_short',
        'location' => 'Wales',
    ],
    11 => [
        'type' => 'debate',
        'title' => 'Senedd Cymru Cofnod',
        'singular' => gettext('debate'),
        'plural' => gettext('debates'),
        'page' => 'senedddebate',
        'page_all' => 'senedddebates',
        'page_year' => 'senedddebatesyear',
        'sidebar' => 'senedddebates',
        'sidebar_short' => 'senedddebates_short',
        'location' => 'Wales',
    ],
];
$hansardmajors[104] = $hansardmajors[4];

$parties =  [
    'Bp'    => 'Bishop',
    'Con'   => 'Conservative',
    'CWM'   => 'Deputy Speaker',
    'DCWM'  => 'Deputy Speaker',
    'Dem'   => 'Liberal Democrat',
    'DU'    => 'Democratic Unionist',
    'Ind'   => 'Independent',
    'Ind Con'       => 'Independent Conservative',
    'Ind Lab'       => 'Independent Labour',
    'Ind UU'        => 'Independent Ulster Unionist',
    'Lab'   => 'Labour',
    'Lab/Co-op'     => 'Labour/Co-operative',
    'LDem'  => 'Liberal Democrat',
    'PC'    => 'Plaid Cymru',
    'Res'   => 'Respect',
    'SDLP'  => 'Social Democratic and Labour Party',
    'SNP'   => 'Scottish National Party',
    'SPK'   => 'Speaker',
    'UKU'   => 'United Kingdom Unionist',
    'UU'    => 'Ulster Unionist',
    'XB'    => 'Crossbench',
];

/*
// The htype field in the hansard database table
$hansardtypes = array (
    10 => 'Section heading',
    11 => 'Subsection heading',
    12 => 'Speech/ question/ answer with speaker ID',
    13 => 'Same without a speaker ID (so procedural)',
    14 => 'Division',
);

// The edit_type field in the editqueue table
$edit_types = array (
    1 => "glossary_create",
    2 => "glossary_modify"
);
*/

// Constants for various house types
define('HOUSE_TYPE_ROYAL', 0);
define('HOUSE_TYPE_COMMONS', 1);
define('HOUSE_TYPE_LORDS', 2);
define('HOUSE_TYPE_NI', 3);
define('HOUSE_TYPE_SCOTLAND', 4);
define('HOUSE_TYPE_WALES', 5);
define('HOUSE_TYPE_LONDON_ASSEMBLY', 6);
