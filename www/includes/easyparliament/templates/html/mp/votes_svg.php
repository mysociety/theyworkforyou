<?php
    $all_lines = array();
    foreach ($segment['votes']->positions as $key_vote) {
        if ( $key_vote['has_strong'] || $key_vote['position'] == 'has never voted on' ) {
            $text = ucfirst(strip_tags($key_vote['desc']));
        } else {
            $text = "We don&rsquo;t have enough information to calculate $full_name &rsquo;s position on " . $key_vote['policy'] . ".";
        }
        $lines = array($text);
        if (strlen($text) > 70) {
            $split_point = strpos($text, ' ', 60);
            $lines = array(substr($text, 0, $split_point), substr($text, $split_point + 1));
        }
        $all_lines = array_merge($all_lines, $lines);
    }
    header("Content-type: image/svg+xml");
    $height = 50 + count($all_lines) * 35;
 ?>
<?php echo '<?xml version="1.0" encoding="iso-8859-1"?>' ?>
  <?php echo '<?xml-stylesheet type="text/css" href="/style/svg.css"?>' ?>
   <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN"
     "http://www.w3.org/TR/2001/
      REC-SVG-20010904/DTD/svg10.dtd">
      <svg width="700" height="<?= $height ?>" viewBox="0 0 700 <?= $height ?>"
     xmlns="http://www.w3.org/2000/svg" 
     xmlns:xlink="http://www.w3.org/1999/xlink">


      <text x="5" y="35" font-family="Helvetica" font-size="30" font-weight="bold">
        How <?= $full_name ?> voted on <?= $segment['title'] ?>
      </text>

    <text x="5" y="50" font-family="Helvetica" font-size="25">
        <?php foreach ($all_lines as $line) { ?>
          <tspan x="5" dy="30">
          <?= $line ?>
          </tspan>
        <?php } ?>
    </text>

    <text x="500" dy="<?= $height - 15 ?>" font-size="10">
      Source: http://www.theyworkforyou.com/
    </text>
  </svg>

