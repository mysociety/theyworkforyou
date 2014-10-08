<?php
/**
 * DebateList Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\HansardList;

class DebateList extends \MySociety\TheyWorkForYou\HansardList {
    public $major = 1;
    public $listpage = 'debates';
    public $commentspage = 'debate';
    public $gidprefix = 'uk.org.publicwhip/debate/';

    public function _get_data_by_recent_mostvotes($args) {
        // Get the most highly voted recent speeches.
        // $args may have 'days'=>7 and/or 'num'=>5
        // or something like that.

        // The most voted on things during how many recent days?
        if (isset($args['days']) && is_numeric($args['days'])) {
            $days = $args['days'];
        } else {
            $days = 7;
        }

        // How many results?
        if (isset($args['num']) && is_numeric($args['num'])) {
            $items_to_list = $args['num'];
        } else {
            $items_to_list = 5;
        }

        $q = $this->db->query("SELECT subsection_id,
                                section_id,
                                htype,
                                gid,
                                major, minor,
                                hdate,
                                speaker_id,
                                epobject.body,
                                SUM(uservotes.vote) + anonvotes.yes_votes AS total_vote
                        FROM    hansard,
                                epobject
                                LEFT OUTER JOIN uservotes ON epobject.epobject_id = uservotes.epobject_id
                                LEFT OUTER JOIN anonvotes ON epobject.epobject_id = anonvotes.epobject_id
                        WHERE       major = :major
                        AND     hansard.epobject_id = epobject.epobject_id
                        AND     hdate >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
                        GROUP BY epobject.epobject_id
                        HAVING  total_vote > 0
                        ORDER BY total_vote DESC
                        LIMIT   $items_to_list
                        ", array(':major' => $this->major));

        // What we return.
        $data = array ();
        $speeches = array();

        if ($q->rows() > 0) {

            for ($n=0; $n<$q->rows(); $n++) {

                $speech = array (
                    'subsection_id' => $q->field($n, 'subsection_id'),
                    'section_id'    => $q->field($n, 'section_id'),
                    'htype'         => $q->field($n, 'htype'),
                    'major'         => $q->field($n, 'major'),
                    'minor'         => $q->field($n, 'minor'),
                    'hdate'         => $q->field($n, 'hdate'),
                    'body'          => $q->field($n, 'body'),
                    'votes'         => $q->field($n, 'total_vote')
                );

                // Remove the "uk.org.publicwhip/blah/" from the gid:
                // (In includes/utility.php)
                $speech['gid'] = fix_gid_from_db( $q->field($n, 'gid') );

                $speech['listurl'] = $this->_get_listurl($speech);

                $speech['speaker'] = $this->_get_speaker($q->field($n, 'speaker_id'), $q->field($n, 'hdate') );

                $speeches[] = $speech;
            }
        }

        if (count($speeches) > 0) {
            // Get the subsection texts.

            for ($n=0; $n<count($speeches); $n++) {
                //if ($this->major == 1) {
                    // Debate.
                    $parent = $this->_get_subsection ($speeches[$n]);

                //} elseif ($this->major == 3) {
                    // Wrans.
                //  $parent = $this->_get_section ($speeches[$n]);
                //}
                // Add the parent's body on...
                //if (isset($parent['body'])) {
                    $speeches[$n]['parent']['body'] = $parent['body'];
                //} else {
                //  $parent = $this->_get_section ($speeches[$n]);
                //  $speeches[$n]['parent']['body'] = $parent['body'];
                //}

            }

            $data['rows'] = $speeches;

        } else {
            $data['rows'] = array ();
        }

        $data['info']['days'] = $days;

        return $data;
    }


    public function total_speeches() {

        $q = $this->db->query("SELECT COUNT(*) AS count FROM hansard WHERE major = :major AND htype = 12", array(':major' => $this->major));

        return $q->field(0, 'count');
    }


    public function biggest_debates($args=array()) {
        // So we can just get the data back for special formatting
        // on the front page, without doing the whole display() thing.
        return $this->_get_data_by_biggest_debates($args);
    }

    public function _get_data_by_recent_debates($args=array()) {
        // Returns an array of some random recent debates from a set number of
        // recent days (that's recent days starting from the most recent day
        // that had any debates on).

        // $args['days'] is the number of days back to look for biggest debates (1 by default).
        // $args['num'] is the number of links to return (1 by default).

        $data = array();

        $params = array();

        // Get the most recent day on which we have a debate.
        $recentday = $this->most_recent_day();
        if (!count($recentday)) return $data;

        if (!isset($args['days']) || !is_numeric($args['days'])) {
            $args['days'] = 1;
        }
        if (!isset($args['num']) || !is_numeric($args['num'])) {
            $args['num'] = 1;
        }

        if ($args['num'] == 1) {
            $datewhere = "h.hdate = :hdate";
            $params[':hdate'] = $recentday['hdate'];
        } else {
            $firstdate = gmdate('Y-m-d', $recentday['timestamp'] - (86400 * $args['days']));
            $datewhere = "h.hdate >= :firstdate
                        AND h.hdate <= :hdate";
            $params[':firstdate'] = $firstdate;
            $params[':hdate'] = $recentday['hdate'];
        }

        $params[':limit'] = $args['num'];
        $params[':major'] = $this->major;

        $query = "SELECT COUNT(*) AS count,
                    body,
                    h.hdate,
                    sech.htype,
                    sech.gid,
                    sech.subsection_id,
                    sech.section_id,
                    sech.epobject_id
            FROM    hansard h, epobject e, hansard sech
            WHERE   h.major = :major
            AND     $datewhere
            AND     h.subsection_id = e.epobject_id
            AND     sech.epobject_id = h.subsection_id
            GROUP BY h.subsection_id
            HAVING  count >= 5
            ORDER BY RAND()
            LIMIT   :limit";

        $q = $this->db->query($query, $params);

        for ($row=0; $row<$q->rows; $row++) {

            // This array just used for getting further data about this debate.
            $item_data = array (
                'major'         => $this->major,
                'gid'           => fix_gid_from_db( $q->field($row, 'gid') ),
                'htype'         => $q->field($row, 'htype'),
                'section_id'    => $q->field($row, 'section_id'),
                'subsection_id' => $q->field($row, 'subsection_id'),
                'epobject_id'   => $q->field($row, 'epobject_id')
            );

            $list_url      = $this->_get_listurl( $item_data );
            $totalcomments = $this->_get_comment_count_for_epobject( $item_data );

            $contentcount  = $q->field($row, 'count');
            $body          = $q->field($row, 'body');
            $hdate         = $q->field($row, 'hdate');

            // If this is a subsection, we're going to prepend the title
            // of the parent section, so let's get that.
            $parentbody = '';
            if ($item_data['htype'] == 11) {
                $r = $this->db->query("SELECT body
                                FROM    epobject
                                WHERE   epobject_id = :epobject_id",
                    array(':epobject_id' => $item_data['section_id']));
                $parentbody = $r->field(0, 'body');
            }

            // Get the question for this item.
            $r = $this->db->query("SELECT e.body,
                                    h.speaker_id, h.hdate
                            FROM    hansard h, epobject e
                            WHERE   h.epobject_id = e.epobject_id
                            AND     h.subsection_id = '" . $item_data['epobject_id'] . "'
                            ORDER BY hpos
                            LIMIT 1
                            ");
            $childbody = $r->field(0, 'body');
            $speaker = $this->_get_speaker($r->field(0, 'speaker_id'), $r->field(0, 'hdate') );

            $data[] = array(
                'contentcount'  => $contentcount,
                'body'          => $body,
                'hdate'         => $hdate,
                'list_url'      => $list_url,
                'totalcomments' => $totalcomments,
                'child'         => array(
                    'body'      => $childbody,
                    'speaker'   => $speaker
                ),
                'parent'        => array(
                    'body'      => $parentbody
                )
            );

        }

        $data = array (
            'info' => array(),
            'data' => $data
        );

        return $data;

    }

    public function _get_data_by_biggest_debates($args=array()) {
        // Returns an array of the debates with most speeches in from
        // a set number of recent days (that's recent days starting from the
        // most recent day that had any debates on).

        // $args['days'] is the number of days back to look for biggest debates.
        // (1 by default)
        // $args['num'] is the number of links to return (1 by default).

        $data = array();

        // Get the most recent day on which we have a debate.
        $recentday = $this->most_recent_day();
        if (!count($recentday))
            return array();

        if (!isset($args['days']) || !is_numeric($args['days'])) {
            $args['days'] = 1;
        }
        if (!isset($args['num']) || !is_numeric($args['num'])) {
            $args['num'] = 1;
        }

        $params = array(':recentdate' => $recentday['hdate']);
        if ($args['num'] == 1) {
            $datewhere = "h.hdate = :recentdate";
        } else {
            $params[':firstdate'] = gmdate('Y-m-d', $recentday['timestamp'] - (86400 * $args['days']));
            $datewhere = "h.hdate >= :firstdate AND h.hdate <= :recentdate";
        }

        $params[':limit'] = $args['num'];
        $params[':major'] = $this->major;

        $q = $this->db->query("SELECT COUNT(*) AS count,
                                body,
                                h.hdate,
                                sech.htype,
                                sech.gid,
                                sech.subsection_id,
                                sech.section_id,
                                sech.epobject_id
                        FROM    hansard h, epobject e, hansard sech
                        WHERE   h.major = :major
                        AND     $datewhere
                        AND     h.subsection_id = e.epobject_id
                        AND     sech.epobject_id = h.subsection_id
                        GROUP BY h.subsection_id
                        ORDER BY count DESC
                        LIMIT :limit", $params);


        for ($row=0; $row<$q->rows; $row++) {

            // This array just used for getting further data about this debate.
            $item_data = array (
                'major'         => $this->major,
                'gid'           => fix_gid_from_db( $q->field($row, 'gid') ),
                'htype'         => $q->field($row, 'htype'),
                'section_id'    => $q->field($row, 'section_id'),
                'subsection_id' => $q->field($row, 'subsection_id'),
                'epobject_id'   => $q->field($row, 'epobject_id')
            );

            $list_url       = $this->_get_listurl( $item_data );
            $totalcomments  = $this->_get_comment_count_for_epobject( $item_data );

            $contentcount   = $q->field($row, 'count');
            $body           = $q->field($row, 'body');
            $hdate          = $q->field($row, 'hdate');


            // This array will be added to $data, which is what gets returned.
            $debate = array (
                'contentcount'  => $contentcount,
                'body'          => $body,
                'hdate'         => $hdate,
                'list_url'      => $list_url,
                'totalcomments' => $totalcomments
            );

            // If this is a subsection, we're going to prepend the title
            // of the parent section, so let's get that.
            if ($item_data['htype'] == 11) {

                $r = $this->db->query("SELECT body
                                FROM    epobject
                                WHERE   epobject_id = :epobject_id",
                    array(':epobject_id' => $item_data['section_id']));
                $debate['parent']['body'] = $r->field(0, 'body');
            }

            $data[] = $debate;
        }

        $data = array (
            'info' => array(),
            'data' => $data
        );

        return $data;

    }

}
