<?php
/*
  Based on wikiproxy.php v0.5 04-10-2004 stefan (wholivesat) whitelabel.org
  scripts/wikipedia-update updates the database of titles each week.

  The bits of code I didn't borrow from elsewhere (and I've credited where) is licenced under the GPL. Do with it what you will, but this is my first php and my first code for 7 years, so I'd appreciate feedback and suggestions via comments on my blog:
  http://www.whitelabel.org/archives/002248.html
  (especially regex optimisations for lines 64 and 65 - ideally a way of making it NOT match if we're within an IMG tag, because then I could drop the antiTaginTag stuff)
*/

function lensort($a, $b) {
  return strlen($a) < strlen($b);
}

function wikipedize($source) {
    $was_array = false;
    if (is_array($source)) {
        $source = join('|||', $source);
        $was_array = true;
    }

  # Set up various variables
  $capsword = "[A-Z][a-zA-Z'0-9,-]*"; # not starting with number, as catches too much
  $fillerwords = "of|and|in|on|under|the|for";
  $middlewordsre = "(?:\s*(?:$capsword|$fillerwords))*";
  $endwordre = "(?:$capsword)"; # and, of etc. can't appear at ends
  $notfiller = "(?:(?!Of|And|In|On|Under|The|For)$capsword)";

  # Match either "Two Endwords" or "Endword and Some Middle Words"
  $phrasewithfiller = "$endwordre$middlewordsre\s*$endwordre";
  $greedyproperre = "/\b$phrasewithfiller\b/ms";

  # And do a match ignoring things starting with filler words
  $greedynofillerstartre = "/\b$notfiller$middlewordsre\s*$endwordre\b/ms";

  # Match without filler words (so if you have a phrase like
  # "Amnesty International and Human Rights Watch" you also get both parts
  # separately "Amnesty International" and "Human Rights Watch")
  $frugalproperre = "/\b$endwordre(?:\s*$endwordre)+\b/ms";

  # And do a greedy without the first word of a sentence
  $greedynotfirst = "/(?:\.|\?|!)\s+\S+\s+($phrasewithfiller)\b/ms";

  # And one for proper nouns in the possessive
  $greedypossessive = "/\b($phrasewithfiller)'s\b/ms";

  preg_match_all($greedyproperre, $source, $propernounphrases1);
  preg_match_all($frugalproperre, $source, $propernounphrases2);
  preg_match_all($greedynotfirst, $source, $propernounphrases3);
  preg_match_all($greedypossessive, $source, $propernounphrases4);
  preg_match_all($greedynofillerstartre, $source, $propernounphrases5);

  # Three Letter Acronyms
  preg_match_all("/\b[A-Z]{2,}/ms", $source, $acronyms);

  # We don't want no steenking duplicates
  $phrases = array_unique(array_merge($propernounphrases1[0], $propernounphrases2[0],
    $propernounphrases3[1], $propernounphrases4[1], $propernounphrases5[0], $acronyms[0]));
  foreach ($phrases as $i => $phrase) {
    $phrases[$i] = str_replace(' ', '_', trim($phrase));
  }

  // Assemble the resulting phrases into a parameter array
  $params = array();
  foreach ($phrases as $i => $phrase) {
    $params[':phrase' . $i] = $phrase;
  }

  # Open up a db connection, and whittle our list down even further, against
  # the real titles.
  $matched = array();
  $db = new ParlDB;
  $source = explode('|||', $source);
  $q = $db->query("SELECT titles.title FROM titles LEFT JOIN titles_ignored ON titles.title=titles_ignored.title WHERE titles.title IN (" . join(',', array_keys($params)) . ") AND titles_ignored.title IS NULL", $params);
  $phrases = array();
  for ($i=0; $i<$q->rows(); $i++) {
    $phrases[] = $q->field($i, 'title');
  }

  # Sort into order, largest first
  usort($phrases, 'lensort');

  foreach ($phrases as $wikistring) {
    $phrase = str_replace('_', ' ', $wikistring);
    $wikistring = str_replace("'", "%27", $wikistring);

    # See if already matched a string this one is contained within
    foreach ($matched as $got) {
      if (strstr($got, $phrase))
        continue 2;
    }

    # Go ahead
    twfy_debug("WIKIPEDIA", "Matched '$phrase'");
    # 1 means only replace one match for phrase per paragraph
    # The regex here ensures that the phrase is only matched if it's not already within <a> tags, preventing double-linking. Kudos to http://stackoverflow.com/questions/7798829/php-regular-expression-to-match-keyword-outside-html-tag-a
    $source = preg_replace ('/\b(' . $phrase . ')\b(?!(?>[^<]*(?:<(?!\/?a\b)[^<]*)*)<\/a>)/', "<a href=\"http://en.wikipedia.org/wiki/$wikistring\">\\1</a>", $source, 1);
    array_push($matched, $phrase);
  }

  if (!$was_array)
    $source = join('|||', $source);

  return $source;
}

#credit: isaac schlueter (lifted from http://uk2.php.net/strip-tags)
function antiTagInTag($content = '', $format = 'htmlhead')
{
  if( !function_exists( 'format_to_output' ) )
    {    // Use the external function if it exists, or fall back on just strip_tags.
      function format_to_output($content, $format)
      {
    return strip_tags($content);
      }
    }
  $contentwalker = 0;
  $length = strlen( $content );
  $tagend = -1;
  for( $tagstart = strpos( $content, '<', $tagend + 1 ) ; $tagstart !== false && $tagstart < strlen( $content ); $tagstart = strpos( $content, '<', $tagend ) )
    {
      // got the start of a tag.  Now find the proper end!
      $walker = $tagstart + 1;
      $open = 1;
      while( $open != 0 && $walker < strlen( $content ) )
    {
      $nextopen = strpos( $content, '<', $walker );
      $nextclose = strpos( $content, '>', $walker );
      if( $nextclose === false )
        {    // ERROR! Open waka without close waka!
          // echo '<code>Error in antiTagInTag - malformed tag!</code> ';
          return $content;
        }
      if( $nextopen === false || $nextopen > $nextclose )
        { // No more opens, but there was a close; or, a close happens before the next open.
          // walker goes to the close+1, and open decrements
          $open --;
          $walker = $nextclose + 1;
        }
      elseif( $nextopen < $nextclose )
        { // an open before the next close
          $open ++;
          $walker = $nextopen + 1;
        }
    }
      $tagend = $walker;
      if( $tagend > strlen( $content ) )
    $tagend = strlen( $content );
      else
    {
      $tagend --;
      $tagstart ++;
    }
      $tag = substr( $content, $tagstart, $tagend - $tagstart );
      $tags[] = '<' . $tag . '>';
      $newtag = format_to_output( $tag, $format );
      $newtags[] = '<' . $newtag . '>';
      $newtag = format_to_output( $tag, $format );
    }
  if (isset($tags)&&isset($newtags)) {
  $content = str_replace($tags, $newtags, $content);
  }
return $content;
}
