<?
// Constants of types used in epobjects and hansard objects
// $Id: dbtypes.php,v 1.8 2009-04-28 13:13:04 matthew Exp $

// The type field in the epobject database table
/*
$eptype = array(
	1 => "hansard",
	2 => "glossary" );
*/

// The major field in the hansard database table
$hansardmajors = array(
	1 => array(
		'type'=>'debate',
		'title'=>"Commons debates",
		'singular'=>'debate',
		'plural'=>'debates',
		'page'=>'debate',
		'page_all'=>'debates',
		'gidvar'=>'id',
		'page_year'=>'debatesyear',
		'sidebar'=>'hocdebates',
		'sidebar_short'=>'hocdebates_short',
		'location' => 'UK',
	),
	2 => array(
		'type'=>'debate',
		'title'=>"Westminster Hall debates",
		'singular'=>'Westminster Hall debate',
		'plural'=>'Westminster Hall debates',
		'page'=>'whall',
		'page_all'=>'whalls',
		'gidvar'=>'gid',
		'page_year'=>'whallyear',
		'sidebar'=>'whalldebates',
		'sidebar_short'=>'whalldebates_short',
		'location' => 'UK',
	),
	# Written answers have minor 1 for question, 2 for answer
	3 => array(
		'type'=>'other',
		'title'=>"Written Answers",
		'singular'=>'answer',
		'plural'=>'answers',
		'page'=>'wrans',
		'page_all'=>'wrans',
		'gidvar'=>'id',
		'page_year'=>'wransyear',
		'sidebar'=>'wrans',
		'sidebar_short'=>'wrans_short',
		'location' => 'UK',
	),
	4 => array(
		'type'=>'other',
		'title'=>"Written Ministerial Statements",
		'singular'=>'statement',
		'plural'=>'statements',
		'page'=>'wms',
		'page_all'=>'wms',
		'gidvar'=>'id',
		'page_year'=>'wmsyear',
		'sidebar'=>'wms',
		'sidebar_short'=>'wms_short',
		'location' => 'UK',
	),
	101 => array(
		'type'=>'debate',
		'title'=>'Lords debates',
		'singular'=>'debate',
		'plural'=>'debates',
		'page'=>'lordsdebate',
		'page_all'=>'lordsdebates',
		'gidvar'=>'gid',
		'page_year'=>'lordsdebatesyear',
		'sidebar'=>'holdebates',
		'sidebar_short'=>'holdebates_short',
		'location' => 'UK',
	),
	5 => array(
		'type'=>'debate',
		'title'=>'Northern Ireland Assembly debates',
		'singular'=>'debate',
		'plural'=>'debates',
		'page'=>'nidebate',
		'page_all'=>'nidebates',
		'gidvar'=>'gid',
		'page_year'=>'nidebatesyear',
		'sidebar'=>'nidebates',
		'sidebar_short'=>'nidebates_short',
		'location' => 'NI',
	),
	6 => array(
		'type' => 'debate',
		'title' => 'Public Bill Committees',
		'singular' => 'clause',
		'plural' => 'clauses',
		'page' => 'pbc_speech',
		'page_all' => 'pbc_clause',
		'gidvar' => 'id',
		# Committees never have a view by date/year
		# 'page_year' => 'pbc_year',
		'sidebar' => 'pbc',
		'sidebar_short' => 'pbc_short',
		'location' => 'UK',
	),
	7 => array(
		'type'=>'debate',
		'title'=>'Scottish Parliament debates',
		'singular'=>'debate',
		'plural'=>'debates',
		'page'=>'spdebates',
		'page_all'=>'spdebates',
		'gidvar'=>'gid',
		'page_year'=>'spdebatesyear',
		'sidebar'=>'spdebates',
		'sidebar_short'=>'spdebates_short',
		'location' => 'Scotland',
	),
	8 => array(
		'type'=>'other',
		'title'=>"Scottish Parliament written answers",
		'singular'=>'answer',
		'plural'=>'answers',
		'page'=>'spwrans',
		'page_all'=>'spwrans',
		'gidvar'=>'id',
		'page_year'=>'spwransyear',
		'sidebar'=>'spwrans',
		'sidebar_short'=>'spwrans_short',
		'location' => 'Scotland',
	),
);
$hansardmajors[104] = $hansardmajors[4];

$parties = array (
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
	'SF'    => 'Sinn Fein',
	'SNP'   => 'Scottish National Party',
	'SPK'   => 'Speaker',
	'UKU'   => 'United Kingdom Unionist',
	'UU'    => 'Ulster Unionist',
	'XB'    => 'Crossbench'
);


/*
// The htype field in the hansard database table
$hansardtypes = array (
	10 => 'Section heading',
	11 => 'Subsection heading',
	12 => 'Speech/ question/ answer with speaker ID',
	13 => 'Same without a speaker ID (so procedural)',
);

// The edit_type field in the editqueue table
$edit_types = array (
	1 => "glossary_create",
	2 => "glossary_modify"
);
*/
