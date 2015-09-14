<?php

namespace MySociety\TheyWorkForYou\SectionView;

class DebatesView extends SectionView {
    protected $major = 1;
    protected $class = 'DEBATELIST';

    protected function display_front() {
        global $PAGE, $DATA, $this_page;

        // No date or debate id. Show some recent debates

        $this_page = "alldebatesfront";

        $DEBATELIST = new \DEBATELIST;
        $debates = array();
        $debates['data'] = $DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>10), 'none');
        $args = array( 'months' => 1 );
        $debates['calendar'] = $DEBATELIST->display('calendar', $args, 'none');
        $debates['rssurl'] = $DATA->page_metadata($this_page, 'rss');

        $this_page = "whallfront";

        $whall = array();

        $WHALLLIST = new \WHALLLIST;
        $whall['data'] = $WHALLLIST->display('biggest_debates', array('days'=>7, 'num'=>10), 'none');
        $args = array( 'months' => 1 );
        $whall['calendar'] = $WHALLLIST->display('calendar', $args, 'none');
        $whall['rssurl'] = $DATA->page_metadata($this_page, 'rss');

        $this_page = "lordsdebatesfront";

        $lords = array();

        $LORDSDEBATELIST = new \LORDSDEBATELIST;
        $lords['data'] = $LORDSDEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>10), 'none');
        $args = array( 'months' => 1 );
        $lords['calendar'] = $LORDSDEBATELIST->display('calendar', $args, 'none');

        $lords['rssurl'] = $DATA->page_metadata($this_page, 'rss');

        $data = array(
            'debates' => $debates,
            'lords' => $lords,
            'whall' => $whall
        );

        $data['template'] = 'section/index';
        $this_page = "alldebatesfront";

        return $data;
    }

    protected function getSearchSections() {
        return array(
            array( 'section' => 'debates', 'title' => 'House of Commons' ),
            array( 'section' => 'lords', 'title' => 'House of Lords' ),
            array( 'section' => 'whall', 'title' => 'Westminster Hall' )
        );
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
        $db = new \ParlDB;
        $gid_type = $this->video_gid_type();
        $vq = $db->query("select id,adate,atime from video_timestamps where gid='uk.org.publicwhip/$gid_type/$row[gid]' and (user_id!=-1 or user_id is null) and deleted=0 order by (user_id is null) limit 1");
        $adate = $vq->field(0, 'adate');
        $time = $vq->field(0, 'atime');
        $videodb = \MySociety\TheyWorkForYou\Utility\Video::dbConnect();
        if (!$videodb) return '';
        $video = \MySociety\TheyWorkForYou\Utility\Video::fromTimestamp($videodb, $adate, $time);
        $start = $video['offset'];
        $out = '';
        if ($count > 1) {
            $out .= '<div class="debate__video" id="video_wrap"><div>';
            if ($row['gid'] != $this->first_gid) {
                $out .= '<p class="video-instructions">This video starts around ' . ($row['hpos']-$heading_hpos) . ' speeches in (<a href="#g' . gid_to_anchor($row['gid']) . '">move there in text</a>)</p>';
            }
        }
        $out .= \MySociety\TheyWorkForYou\Utility\Video::object($video['id'], $start, "$gid_type/$row[gid]");
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
