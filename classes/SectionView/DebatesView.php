<?php

namespace MySociety\TheyWorkForYou\SectionView;

class DebatesView extends SectionView {
    protected $major = 1;
    protected $class = 'DEBATELIST';

    protected function display_front() {
        global $PAGE, $DATA, $this_page;

        // No date or debate id. Show some recent debates

        $this_page = "alldebatesfront";
        $PAGE->page_start();
        $PAGE->stripe_start();
    ?>
        <h2>Recent House of Commons debates</h2>
    <?php

        $DEBATELIST = new \DEBATELIST;
        $DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>10));

        $rssurl = $DATA->page_metadata($this_page, 'rss');
        $PAGE->stripe_end(array(
            # XXX When this is three columns, not one, put this search at the top spanning...
            array (
                'type' => 'include',
                'content' => 'minisurvey'
            ),
            array(
                'type' => 'html',
                'content' => '
    <div class="block">
    <h4>Search debates</h4>
    <div class="blockbody">
    <form action="/search/" method="get">
    <p><input type="text" name="q" id="search_input" value="" size="40"> <input type="submit" value="Go">
    <br><input type="checkbox" name="section[]" value="debates" checked id="section_commons">
    <label for="section_commons">Commons</label>
    <input type="checkbox" name="section[]" value="whall" checked id="section_whall">
    <label for="section_whall">Westminster Hall</label>
    <input type="checkbox" name="section[]" value="lords" checked id="section_lords">
    <label for="section_lords">Lords</label>
    </p>
    </form>
    </div>
    </div>
    ',
        ),
            array (
                'type' => 'include',
                'content' => 'calendar_hocdebates'
            ),
            array (
                'type' => 'include',
                'content' => "hocdebates"
            ),
            array (
                'type' => 'html',
                'content' => '<div class="block">
    <h4>RSS feed</h4>
    <p><a href="' . WEBPATH . $rssurl . '"><img align="middle" src="http://www.theyworkforyou.com/images/rss.gif" border="0" alt="RSS feed"></a>
    <a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></p>
    </div>'
            )
        ));

        $this_page = "whallfront";
        $PAGE->page_start();
        $PAGE->stripe_start();
    ?>
        <h2>Recent Westminster Hall debates</h2>
    <?php

        $WHALLLIST = new \WHALLLIST;
        $WHALLLIST->display('biggest_debates', array('days'=>7, 'num'=>10));

        $rssurl = $DATA->page_metadata($this_page, 'rss');
        $PAGE->stripe_end(array(
            array (
                'type' => 'include',
                'content' => 'minisurvey'
            ),
            array (
                'type' => 'include',
                'content' => 'calendar_whalldebates'
            ),
            array (
                'type' => 'include',
                'content' => "whalldebates"
            ),
            array (
                'type' => 'html',
                'content' => '<div class="block">
    <h4>RSS feed</h4>
    <p><a href="' . WEBPATH . $rssurl . '"><img alt="RSS feed" border="0" align="middle" src="http://www.theyworkforyou.com/images/rss.gif"></a>
    <a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></p>
    </div>'
            )
        ));

        $this_page = "lordsdebatesfront";
        $PAGE->page_start();
        $PAGE->stripe_start();
    ?>
        <h2>Recent House of Lords debates</h2>
    <?php

        $LORDSDEBATELIST = new \LORDSDEBATELIST;
        $LORDSDEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>10));

        $rssurl = $DATA->page_metadata($this_page, 'rss');
        $PAGE->stripe_end(array(
            array (
                'type' => 'nextprev'
            ),
            array (
                'type' => 'include',
                'content' => 'minisurvey'
            ),
            array (
                'type' => 'include',
                'content' => 'calendar_holdebates'
            ),
            array (
                'type' => 'include',
                'content' => "holdebates"
            ),
            array (
                'type' => 'html',
                'content' => '<div class="block">
    <h4>RSS feed</h4>
    <p><a href="' . WEBPATH . $rssurl . '"><img alt="RSS feed" border="0" align="middle" src="http://www.theyworkforyou.com/images/rss.gif"></a>
    <a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></p>
    </div>'
            )
        ));
    }

    private $first_speech_displayed = false; // We want to know when to insert the video
    private $first_video_displayed = false; // or the advert to do the video
    private $first_gid = '';

    protected function get_video_html($row, $heading_hpos, $speeches) {
        if (!$this->first_gid) $this->first_gid = $row['gid'];

        $video_content = '';
        if (!$this->first_video_displayed && $row['video_status']&4 && !($row['video_status']&8)) {
            $video_content = $this->video_sidebar($row, $heading_hpos, $speeches);
            $this->first_video_displayed = true;
        }
        if (!$video_content && !$this->first_speech_displayed && $row['video_status']&1 && !($row['video_status']&12)) {
            $video_content = $this->video_advert($row);
            $this->first_speech_displayed = true;
        }
        return $video_content;
    }

    private function video_gid_type() {
        if ($this->major == 1) {
            return 'debate';
        } elseif ($this->major == 101) {
            return 'lords';
        } else {
            return 'unknown';
        }
    }

    private function video_sidebar($row, $heading_hpos, $count) {
        include_once INCLUDESPATH . 'easyparliament/video.php';
        $db = new \ParlDB;
        $gid_type = $this->video_gid_type();
        $vq = $db->query("select id,adate,atime from video_timestamps where gid='uk.org.publicwhip/$gid_type/$row[gid]' and (user_id!=-1 or user_id is null) and deleted=0 order by (user_id is null) limit 1");
        $adate = $vq->field(0, 'adate');
        $time = $vq->field(0, 'atime');
        $videodb = video_db_connect();
        if (!$videodb) return '';
        $video = video_from_timestamp($videodb, $adate, $time);
        $start = $video['offset'];
        $out = '';
        if ($count > 1) {
            $out .= '<div class="debate__video" id="video_wrap"><div>';
            if ($row['gid'] != $this->first_gid) {
                $out .= '<p class="video-instructions">This video starts around ' . ($row['hpos']-$heading_hpos) . ' speeches in (<a href="#g' . gid_to_anchor($row['gid']) . '">move there in text</a>)</p>';
            }
        }
        $out .= video_object($video['id'], $start, "$gid_type/$row[gid]");
        $flashvars = 'gid=' . "$gid_type/$row[gid]" . '&amp;file=' . $video['id'] . '&amp;start=' . $start;
        $out .= "<strong>Embed this video</strong><p class='video-instructions'>Copy and paste this code on your website</p><input readonly onclick='this.focus();this.select();' type='text' name='embed' size='40' value=\"<embed src='http://www.theyworkforyou.com/video/parlvid.swf' width='320' height='230' allowfullscreen='true' allowscriptaccess='always' flashvars='$flashvars'></embed>\">";
        if ($count > 1) {
            $out .= '<p class="hide-video"><a href="" onclick="return showVideo();">Hide</a></p>';
            $out .= '</div></div>';
            $out .= '<div id="video_show" class="show-video" style="display:none;">
    <p style="margin:0"><a href="" onclick="return hideVideo();">Show video</a></p></div>';
        }
        return $out;
    }

    private function video_advert($row) {
        $gid_type = $this->video_gid_type();
        return '
    <div style="border:solid 1px #9999ff; background-color: #ccccff; padding: 4px; text-align: center;
    background-image: url(\'/images/video-x-generic.png\'); background-repeat: no-repeat; padding-left: 40px;
    background-position: 0 2px; margin-bottom: 1em;">
    Help us <a href="/video/?from=debate&amp;gid=' . $gid_type . '/' . $row['gid'] . '">match the video for this speech</a>
    to get the right video playing here
    </div>
    ';
    }

}
