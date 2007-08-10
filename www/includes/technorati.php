<?php
  /*
  
  mysql> load data infile '/home/stefan/whitelabel.org/wp/convertedtitles' ignore into table titles;
  */   
function technorati_pretty() {
	global $arItems;
	technorati('http://www.theyworkforyou.com' . $_SERVER['REQUEST_URI']);
	$body = '';
	foreach ($arItems as $item) {
		$created = strtotime($item->xCreated);
		$ago = time() - $created; $string = 'second';
		if ($ago > 59) { $ago /= 60; $string = 'minute'; }
		if ($string == 'minute' && $ago > 59) { $ago /= 60; $string = 'hour'; }
		if ($string == 'hour' && $ago > 23) { $ago /= 24; $string = 'day'; }
		if ($string == 'day' && $ago > 13) { $ago /= 7; $string = 'week'; }
		$ago = round($ago); if ($ago != 1) $string .= 's';
		$body .= '<li><a href="' . $item->xLink . '">' . $item->xTitle . '</a> ('.$ago.' '.$string.' ago)</li>';
	}
	if ($body) {
		$body = "<ul>$body</ul>";
	}
	return $body;
}

class xItem {
  var $xTitle;
  var $xLink;
  var $xPermalink;
}

function technorati ($url){
  global $arItems, $itemCount;

# array of technorati links
  $arItems = array();
  
  $itemCount = 0;
  
# right. now get the technorati links.
  doCosmos($url);
}

## functions/classes bits and pieces  from elsewhere below

//This is the only line that you need to change. Go to http://www.technorati.com/members/apikey.html
//(sign up if you have to) and put your API key in the url below. Change the URL to your blog URL.

// from http://wordpress.org/support/10/2363

function doCosmos ($urlstring){

  $techRati = "http://api.technorati.com/cosmos?format=xml&url=$urlstring&key=7e64960cc7e9b1cb4315e56a6544fce7" ;

  /* No caching for now */
#generate cachefilename
//  preg_match ("/\d{7}/", $urlstring, $nums) ;

//  $cacheFilename= $nums[0];

  // cache for 30 mins only
  //  if (!file_exists("cache/{$cacheFilename}.xml")||  (time() - filemtime("cache/{$cacheFilename}.xml") > 1800)||filesize ("cache/{$cacheFilename}.xml")==0 ){ 

#echo "cache miss!";

    $a = file($techRati);
    if (!$a) return false;
    set_time_limit (10);
    $contents = implode('', $a);
    //    $cachefp = fopen("cache/{$cacheFilename}.xml", "w");
    //    fwrite($cachefp, $contents);
    //    fclose($cachefp);


  $xml_parser = xml_parser_create();
  xml_set_element_handler($xml_parser, "start1Element", "end1Element");
  xml_set_character_data_handler($xml_parser, "character1Data");
  //character1Data = fopen("cache/$cacheFilename.xml", "r")
  #  $fp = fopen($a, "r")
  #  or die("Error reading XML data.");
  #while ($data = fread($fp, 16384)) {
    // Parse each 4KB chunk with the XML parser created above
    xml_parse($xml_parser, $contents, TRUE);

#echo "reading...";

#  }
#  fclose($fp);
  xml_parser_free($xml_parser);

#echo "parsing complete";
#  global $arItems;
#  global $itemCount;
  // write out the items

#echo count($arItems);

}

function start1Element($parser, $tagName, $attrs) {
  global $curTag;
  $curTag .= "^$tagName";
  // 	echo $counter." ".$curTag."<br> ";
}


function end1Element($parser, $tagName) {
  global $curTag;
  $caret_pos = strrpos($curTag,'^');
  $curTag = substr($curTag,0,$caret_pos);
  // 	echo $counter." ".$curTag."<br> ";
}

function character1Data($parser, $data) {
  global $itemCount, $curTag, $arItems;
  $titleKey = "^TAPI^DOCUMENT^ITEM^WEBLOG^NAME";
  $permalinkKey = "^TAPI^DOCUMENT^ITEM^NEARESTPERMALINK";
  $linkKey = "^TAPI^DOCUMENT^ITEM^WEBLOG^URL";
  $createdKey = "^TAPI^DOCUMENT^ITEM^LINKCREATED";
  if ($curTag == $titleKey) {
    // make new xItem

    $arItems[$itemCount] = new xItem();
    // set new item object's properties
    $arItems[$itemCount]->xTitle = $data;
  }
  elseif ($curTag == $permalinkKey) {
    $arItems[$itemCount]->xPermalink = $data;
    #    $itemCount++;
  }
  elseif ($curTag == $linkKey) {
    $arItems[$itemCount]->xLink = $data;
    #$itemCount++;
  } elseif ($curTag == $createdKey) {
	  $arItems[$itemCount]->xCreated = $data;
	  $itemCount++;
	}
}

?>
