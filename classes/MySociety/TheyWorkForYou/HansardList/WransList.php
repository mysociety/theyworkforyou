<?php
/**
 * WransList Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\HansardList;

class WransList extends \MySociety\TheyWorkForYou\HansardList {
    public $major = 3;
    public $listpage = 'wrans';
    public $commentspage = 'wrans'; // We don't have a separate page for wrans comments.
    public $gidprefix = 'uk.org.publicwhip/wrans/';

    public function total_questions() {
        $q = $this->db->query("SELECT COUNT(*) AS count FROM hansard WHERE major = :major AND minor = 1", array(':major' => $this->major));
        return $q->field(0, 'count');
    }

    public function _get_data_by_recent_wrans ($args=array()) {
        global $hansardmajors;

        // $args['days'] is the number of days back to look for biggest debates.
        // (1 by default)
        // $args['num'] is the number of links to return (1 by default).

        $data = array();

        $params = array();

        // Get the most recent day on which we have wrans.
        $recentday = $this->most_recent_day();
        if (!count($recentday))
            return $data;

        if (!isset($args['days']) || !is_numeric($args['days'])) {
            $args['days'] = 1;
        }
        if (!isset($args['num']) || !is_numeric($args['num'])) {
            $args['num'] = 1;
        }

        if ($args['num'] == 1) {
            $datewhere = "h.hdate = :datewhere";
            $params[':datewhere'] = $recentday['hdate'];
        } else {
            $firstdate = gmdate('Y-m-d', $recentday['timestamp'] - (86400 * $args['days']));
            $datewhere = "h.hdate >= :firstdate AND h.hdate <= :hdate";
            $params[':firstdate'] = $firstdate;
            $params[':hdate'] = $recentday['hdate'];
        }


        // Get a random selection of subsections in wrans.
        if ($hansardmajors[$this->major]['location'] == 'Scotland') {
            $htype = 'htype = 10 and section_id = 0';
        } else {
            $htype = 'htype = 11 and section_id != 0';
        }

        $params[':limit'] = $args['num'];
        $params[':major'] = $this->major;

        $query = "SELECT e.body,
                    h.hdate,
                    h.htype,
                    h.gid,
                    h.subsection_id,
                    h.section_id,
                    h.epobject_id
            FROM    hansard h, epobject e
            WHERE   h.major = :major
            AND     $htype
            AND     subsection_id = 0
            AND     $datewhere
            AND     h.epobject_id = e.epobject_id
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

            $list_url       = $this->_get_listurl( $item_data );
            $totalcomments  = $this->_get_comment_count_for_epobject( $item_data );

            $body           = $q->field($row, 'body');
            $hdate          = $q->field($row, 'hdate');

            // Get the parent section for this item.
            $parentbody = '';
            if ($q->field($row, 'section_id')) {
                $r = $this->db->query("SELECT e.body
                            FROM    hansard h, epobject e
                            WHERE   h.epobject_id = e.epobject_id
                            AND     h.epobject_id = '" . $q->field($row, 'section_id') . "'
                            ");
                $parentbody = $r->field(0, 'body');
            }

            // Get the question for this item.
            $r = $this->db->query("SELECT e.body,
                                    h.speaker_id, h.hdate
                            FROM    hansard h, epobject e
                            WHERE   h.epobject_id = e.epobject_id
                            AND     h.subsection_id = '" . $q->field($row, 'epobject_id') . "'
                            ORDER BY hpos
                            LIMIT 1
                            ");
            $childbody = $r->field(0, 'body');
            $speaker = $this->_get_speaker($r->field(0, 'speaker_id'), $r->field(0, 'hdate') );

            $data[] = array (
                'body'          => $body,
                'hdate'         => $hdate,
                'list_url'      => $list_url,
                'totalcomments' => $totalcomments,
                'child'         => array (
                    'body'      => $childbody,
                    'speaker'   => $speaker
                ),
                'parent'        => array (
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

}
