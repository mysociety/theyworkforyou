<?
// Constants of types used in epobjects and hansard objects
// $Id: dbtypes.php,v 1.3 2006-12-10 23:35:41 matthew Exp $

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
	),
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
	),
	101 => array(
		'type'=>'debate',
		'title'=>'Lords debates',
		'singular'=>'debate',
		'plural'=>'debates',
		'page'=>'lordsdebates',
		'page_all'=>'lordsdebates',
		'gidvar'=>'gid',
		'page_year'=>'lordsdebatesyear',
		'sidebar'=>'holdebates',
		'sidebar_short'=>'holdebates_short',
	),
	5 => array(
		'type'=>'debate',
		'title'=>'Northern Ireland Assembly debates',
		'singular'=>'debate',
		'plural'=>'debates',
		'page'=>'nidebates',
		'page_all'=>'nidebates',
		'gidvar'=>'gid',
		'page_year'=>'nidebatesyear',
		'sidebar'=>'nidebates',
		'sidebar_short'=>'nidebates_short',
	),
);
$hansardmajors[104] = $hansardmajors[4];

$parties = array (
	'Bp'    => 'Bishop',
	'Con'   => 'Conservative',
	'CWM'   => 'Deputy-Speaker',
	'DCWM'  => 'Deputy-Speaker',
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
	10 => 'Debate section',
	11 => 'Debate subsection',
	12 => 'Debate speech',
	13 => 'Debate procedural',

	60 => 'Written answer',
	61 => 'Wrans question',
	62 => 'Wrans reply',	
);

// The edit_type field in the editqueue table
$edit_types = array (
	1 => "glossary_create",
	2 => "glossary_modify"
);
*/
?>
