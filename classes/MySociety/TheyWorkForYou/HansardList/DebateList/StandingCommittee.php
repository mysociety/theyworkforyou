<?php
/**
 * StandingCommittee Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\HansardList\DebateList;

class StandingCommittee extends \MySociety\TheyWorkForYou\HansardList\DebateList {
    public $major = 6;
    public $listpage = 'pbc_clause';
    public $commentspage = 'pbc_speech';
    public $gidprefix = 'uk.org.publicwhip/standing/';

    public function __construct($session='', $title='') {
        parent::__construct();
        $this->bill_title = $title;
        $title = str_replace(' ', '_', $title);
        $this->url = urlencode($session) . '/' . urlencode($title) . '/';
    }

    public function _get_committee($bill_id) {
        include_once INCLUDESPATH."easyparliament/member.php";
        $q = $this->db->query(
            'select count(*) as c from hansard
                where major=6 and minor=:bill_id and htype=10',
            array(':bill_id' => $bill_id)
        );
        $sittings = $q->field(0, 'c');
        $q = $this->db->query(
            'select member_id,sum(attending) as attending, sum(chairman) as chairman
                from pbc_members
                where bill_id = :bill_id group by member_id',
            array(':bill_id' => $bill_id));
        $comm = array('sittings'=>$sittings);
        for ($i=0; $i<$q->rows(); $i++) {
            $member_id = $q->field($i, 'member_id');
            $mp = new \MySociety\TheyWorkForYou\Member(array('member_id'=>$member_id));
            $attending = $q->field($i, 'attending');
            $chairman = $q->field($i, 'chairman');
            $arr = array(
                'name' => $mp->full_name(),
                'attending' => $attending,
            );
            if ($chairman) {
                $comm['chairmen'][$member_id] = $arr;
            } else {
                $comm['members'][$member_id] = $arr;
            }
        }
        return $comm;
    }

    public function _get_data_by_bill($args) {
        global $DATA, $this_page;
        $data = array();
        $input = array (
            'amount' => array (
                'body' => true,
                'comment' => true,
                'excerpt' => true
            ),
            'where' => array (
                'htype=' => '10',
                'major=' => $this->major,
                'minor=' => $args['id'],
            ),
            'order' => 'hdate,hpos'
        );
        $sections = $this->_get_hansard_data($input);
        if (count($sections) > 0) {
            $data['rows'] = array();
            for ($n=0; $n<count($sections); $n++) {
                $sectionrow = $sections[$n];
                list($sitting, $part) = $this->_get_sitting($sectionrow['gid']);
                $sectionrow['sitting'] = $sitting;
                $sectionrow['part'] = $part;
                $input = array (
                    'amount' => array (
                        'body' => true,
                        'comment' => true,
                        'excerpt' => true
                    ),
                    'where' => array (
                        'section_id='   => $sectionrow['epobject_id'],
                        'htype='    => '11',
                        'major='    => $this->major
                    ),
                    'order' => 'hpos'
                );
                $rows = $this->_get_hansard_data($input);
                array_unshift ($rows, $sectionrow);
                $data['rows'] = array_merge ($data['rows'], $rows);
            }
        }
        $data['info']['bill'] = $args['title'];
        $data['info']['major'] = $this->major;
        $data['info']['committee'] = $this->_get_committee($args['id']);
        $DATA->set_page_metadata($this_page, 'title', $args['title']);
        return $data;
    }

    public function _get_data_by_session($args) {
        global $DATA, $this_page;
        $session = $args['session'];
        $q = $this->db->query(
            'select id, title from bills where session = :session order by title',
            array(':session' => $session)
        );
        $bills = array();
        for ($i=0; $i<$q->rows(); $i++) {
            $bills[$q->field($i, 'id')] = $q->field($i, 'title');
        }
        if (!count($bills)) {
            return array();
        }
        $q = $this->db->query('select minor,count(*) as c from hansard where major=6 and htype=12
            and minor in (' . join(',', array_keys($bills)) . ')
            group by minor');
        $counts = array();
        # $comments = array();
        for ($i=0; $i<$q->rows(); $i++) {
            $minor = $q->field($i, 'minor');
            $counts[$minor] = $q->field($i, 'c');
            # $comments[$minor] = 0;
        }
        /*
        $q = $this->db->query('select minor,epobject_id from hansard where major=6 and htype=10
            and minor in (' . join(',', array_keys($bills)) . ')');
        for ($i=0; $i<$q->rows(); $i++) {
            $comments[$q->field($i, 'minor')] += $this->_get_comment_count_for_epobject(array(
                'epobject_id' => $q->field($i, 'epobject_id'),
                'htype' => 10,
            ));
        }
        */
        $data = array();
        foreach ($bills as $id => $title) {
            $data[] = array(
                'title' => $title,
                'url' => "/pbc/" . urlencode($session) . '/' . urlencode(str_replace(' ', '_', $title)) . '/',
                'contentcount' => isset($counts[$id]) ? $counts[$id] : '???',
                # 'totalcomments' => isset($comments[$id]) ? $comments[$id] : '???',
            );
        }

        $YEARURL = new \MySociety\TheyWorkForYou\Url('pbc_session');
        $nextprev = array();
        $nextprev['prev'] = array ('body' => 'Previous session', 'title'=>'');
        $nextprev['next'] = array ('body' => 'Next session', 'title'=>'');
        $q = $this->db->query(
            "SELECT session FROM bills WHERE session < :session ORDER BY session DESC LIMIT 1",
            array(':session' => $session)
        );
        $prevyear = $q->field(0, 'session');
        $q = $this->db->query(
            "SELECT session FROM bills WHERE session > :session ORDER BY session ASC LIMIT 1",
            array(':session' => $session)
        );
        $nextyear = $q->field(0, 'session');
        if ($prevyear) {
            $nextprev['prev']['url'] = $YEARURL->generate() . $prevyear . '/';
        }
        if ($nextyear) {
            $nextprev['next']['url'] = $YEARURL->generate() . $nextyear . '/';
        }
        $DATA->set_page_metadata($this_page, 'nextprev', $nextprev);

        return $data;
    }

    public function _get_data_by_recent_pbc_debates($args) {
        if (!isset($args['num'])) $args['num'] = 20;
        $q = $this->db->query('select gid, minor, hdate from hansard
            where htype=10 and major=6
            order by hdate desc limit ' . $args['num']);
        $data = array();
        for ($i=0; $i<$q->rows(); $i++) {
            $minor = $q->field($i, 'minor');
            $gid = $q->field($i, 'gid');
            $hdate = format_date($q->field($i, 'hdate'), LONGDATEFORMAT);
            $qq = $this->db->query('select title, session from bills where id='.$minor);
            $title = $qq->field(0, 'title');
            $session = $qq->field(0, 'session');
            list($sitting, $part) = $this->_get_sitting($gid);
            $sitting_txt = make_ranking($sitting) . ' sitting';
            if ($part>0) $sitting .= ", part $part";
            $data[$hdate][] = array(
                'bill'=> $title,
                'sitting' => $sitting_txt,
                'url' => "/pbc/$session/" . urlencode(str_replace(' ','_',$title)) . '/#sitting' . $sitting,
            );
        }
        return $data;
    }

    # Given a GID, parse out the sitting number and optional part from it
    public function _get_sitting($gid) {
        if (preg_match('#_(\d\d)-(\d)_#', $gid, $m))
            return array($m[1]+0, $m[2]);
        return array(0, 0);
    }
}
