<?php

namespace MySociety\TheyWorkForYou\SectionView;

class SpwransView extends WransView {
    protected $major = 8;
    protected $class = 'SPWRANSLIST';

    public function display() {
        global $PAGE;
        if ($spid = get_http_var('spid')) {
            $this->spwrans_redirect($spid);
            $PAGE->page_end();
        } else {
            return parent::display();
        }
    }

    private function spwrans_redirect($spid) {
        global $PAGE;

        # We have a Scottish Parliament ID, need to find the date
        $SPWRANSLIST = new \SPWRANSLIST;
        $gid = $SPWRANSLIST->get_gid_from_spid($spid);
        if ($gid) {
            if (preg_match('/uk\.org\.publicwhip\/spwa\/(\d{4}-\d\d-\d\d\.(.*))/',$gid,$m)) {
                $URL = new \URL('spwrans');
                $URL->reset();
                $URL->insert( array('id' => $m[1]) );
                $fragment_identifier = '#g' . $m[2];
                header('Location: http://' . DOMAIN . $URL->generate('none') . $fragment_identifier, true, 303);
                exit;
            } elseif (preg_match('/uk\.org\.publicwhip\/spor\/(\d{4}-\d\d-\d\d\.(.*))/',$gid,$m)) {
                $URL = new \URL('spdebates');
                $URL->reset();
                $URL->insert( array('id' => $m[1]) );
                $fragment_identifier = '#g' . $m[2];
                header('Location: http://' . DOMAIN . $URL->generate('none') . $fragment_identifier, true, 303);
                exit;
            } else {
                $PAGE->error_message ("Strange GID ($gid) for that Scottish Parliament ID.");
            }
        }
        $PAGE->error_message ("Couldn't match that Scottish Parliament ID to a GID.");
    }

    protected function get_question_mentions_html($row_data) {
        if( count($row_data) == 0 ) {
            return '';
        }
        $result = '<ul class="question-mentions">';
        $nrows = count($row_data);
        $last_date = NULL;
        $first_difference_output = TRUE;
        // Keep the references until after the history that's in a timeline:
        $references = array();
        for( $i = 0; $i < $nrows; $i++ ) {
            $row = $row_data[$i];
            if( ! $row["date"] ) {
                // If this mention isn't associated with a date, the difference won't be interesting.
                $last_date = NULL;
            }
            $description = '';
            if ($last_date && ($last_date != $row["date"])) {
                // Calculate how long the gap was in days:
                $daysdiff = (integer)((strtotime($row["date"]) - strtotime($last_date)) / 86400);
                $daysstring = ($daysdiff == 1) ? "day" : "days";
                $further = "";
                if( $first_difference_output ) {
                    $first_difference_output = FALSE;
                } else {
                    $further = " a further";
                }
                $description = "\n<span class=\"question-mention-gap\">After$further $daysdiff $daysstring,</span> ";
            }
            $reference = FALSE;
            $inner = "BUG: Unknown mention type $row[type]";
            $date = format_date($row['date'], SHORTDATEFORMAT);
            switch ($row["type"]) {
                case 1:
                    $inner = "Mentioned in <a href=\"$row[url]\">today's business on $date</a>";
                    break;
                case 2:
                    $inner = "Mentioned in <a href=\"$row[url]\">tabled oral questions on $date</a>";
                    break;
                case 3:
                    $inner = "Mentioned in <a href=\"$row[url]\">tabled written questions on $date</a>";
                    break;
                case 4:
                    if( preg_match('/^uk.org.publicwhip\/spq\/(.*)$/',$row['gid'],$m) ) {
                        $URL = new \URL("spwrans");
                        $URL->insert( array('spid' => $m[1]) );
                        $relative_url = $URL->generate("none");
                        $inner = "Given a <a href=\"$relative_url\">written answer on $date</a>";
                    }
                    break;
                case 5:
                    $inner = "Given a holding answer on $date";
                    break;
                case 6:
                    if( preg_match('/^uk.org.publicwhip\/spor\/(.*)$/',$row['mentioned_gid'],$m) ) {
                        $URL = new \URL("spdebates");
                        $URL->insert( array('id' => $m[1]) );
                        $relative_url = $URL->generate("none");
                        $inner = "<a href=\"$relative_url\">Asked in parliament on $date</a>";
                    }
                    break;
                case 7:
                    if( preg_match('/^uk.org.publicwhip\/spq\/(.*)$/',$row['mentioned_gid'],$m) ) {
                        $referencing_spid = $m[1];
                        $URL = new \URL("spwrans");
                        $URL->insert( array('spid' => $referencing_spid) );
                        $relative_url = $URL->generate("none");
                        $inner = "Referenced in <a href=\"$relative_url\">question $referencing_spid</a>";
                        $reference = TRUE;
                    }
                    break;
            }
            if( $reference ) {
                $references[] = "\n<li>$inner.";
            } else {
                $result .= "\n<li>$description$inner.</span>";
                $last_date = $row["date"];
            }
        }
        foreach ($references as $reference_span) {
            $result .= $reference_span;
        }
        $result .= '</ul>';
        return $result;
    }

}
