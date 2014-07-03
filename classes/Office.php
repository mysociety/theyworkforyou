<?php
/**
 * Office Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Office
 */

class Office {

    public $title;
    public $from_date;
    public $to_date;
    public $source;

    public function __toString() {
        if (isset ($this->title)){
            return (string) $this->title;
        } else {
            return 'Unnamed Office';
        }
    }

    public function pretty_dates() {
        $output = '';
        if (!($this->source == 'chgpages/selctee' && $this->from_date == '2004-05-28')
            && !($this->source == 'chgpages/privsec' && $this->from_date == '2004-05-13')) {
            if ($this->source == 'chgpages/privsec' && $this->from_date == '2005-11-10')
                $output .= 'before ';
            $output .= format_date($this->from_date,SHORTDATEFORMAT) . ' ';
        }
        $output .= 'to ';
        if ($this->source == 'chgpages/privsec' && $this->to_date == '2005-11-10')
            $output .= 'before ';
        if ($this->source == 'chgpages/privsec' && $this->to_date == '2009-01-16')
            $output .= '<a href="/help/#pps_unknown">unknown</a>';
        else
            $output .= format_date($this->to_date, SHORTDATEFORMAT);

        return $output;
    }

}