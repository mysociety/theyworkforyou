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

    /**
    * To String
    *
    * Return the office title as a string.
    *
    * @return string The title of the office, or "Unnamed Office"
    */

    public function __toString() {
        if (isset ($this->title)){
            return (string) $this->title;
        } else {
            return 'Unnamed Office';
        }
    }

    /**
    * Pretty Dates
    *
    * Return a string containing prettified dates of this office.
    *
    * 2004-05-28 and 2004-05-13 are the first dates for data scraped from the
    * old selctee/privsec pages on parliament.uk (you can see this in
    * cmpages/chgpages' privsec0001_2004-06-08.html and
    * selctee0001_2004-06-08.html). So if the date is those dates for those two
    * things, you don't want to display it because it's not a known start date,
    * it could have been before that date. 2005-11-10 is because the PPS changes
    * did not all happen on that date but the website did not update until that
    * date so it outputs "before" in either from/to date in that case.
    * 2009-01-16 is the last date before the page disappeared off parliament.uk
    * entirely so that displays that fact that after then we don't know.
    *
    * @todo https://github.com/mysociety/theyworkforyou/issues/632
    *
    * @return string The dates of this office in a readable form.
    */

    public function pretty_dates() {

        $output = '';

        if (
            !($this->source == 'chgpages/selctee' && $this->from_date == '2004-05-28') AND
            !($this->source == 'chgpages/privsec' && $this->from_date == '2004-05-13')
        ) {
            if ($this->source == 'chgpages/privsec' && $this->from_date == '2005-11-10') {
                $output .= 'before ';
            }
            $output .= format_date($this->from_date,SHORTDATEFORMAT) . ' ';
        }

        $output .= 'to ';

        if ($this->source == 'chgpages/privsec' && $this->to_date == '2005-11-10') {
            $output .= 'before ';
        }

        if ($this->source == 'chgpages/privsec' && $this->to_date == '2009-01-16') {
            $output .= '<a href="/help/#pps_unknown">unknown</a>';
        } else {
            $output .= format_date($this->to_date, SHORTDATEFORMAT);
        }

        return $output;

    }

}
