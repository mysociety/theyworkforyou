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
    /** Offices that are a seat on a committee. */
    public const COMMITTEE_TYPES = ['committee'];

    /** Offices that are a post held in the chamber rather than on a committee. */
    public const POST_TYPES = ['government', 'opposition', 'parliamentary', 'other'];

    /** Category applied by UK Parliament to public bill committees. */
    private const PUBLIC_BILL_TAG = '(HC) Public bill committee';

    /** Placeholder used when we have no start date for a membership. */
    private const UNKNOWN_DATE = '1000-01-01';

    public $title;
    public $from_date;
    public $to_date;
    public $source;
    public $position = "";
    public $position_cy = "";
    public $dept = "";
    public $slug = "";
    public $desc = "";
    public $external_url = "";
    public $org_id = "";
    public $post_type = "other";
    public $parliament = "";
    public $tags = "";

    /**
     * Is this office a seat on a committee?
     */
    public function isCommittee(): bool {
        return in_array($this->post_type, self::COMMITTEE_TYPES);
    }

    /**
     * Public bill committees are shown separately, from the pbc_members data,
     * which also knows about the bill being scrutinised.
     */
    public function isPublicBillCommittee(): bool {
        return in_array(self::PUBLIC_BILL_TAG, explode(',', $this->tags));
    }

    /**
     * The role held, in the reader's language where we have it.
     */
    public function role(): string {
        if (LANGUAGE == 'cy' && $this->position_cy) {
            return $this->position_cy;
        }
        return $this->position;
    }


    /**
     * To String
     *
     * Return the office title as a string.
     *
     * @return string The title of the office, or "Unnamed Office"
     */

    public function __toString() {
        if (isset($this->title)) {
            return (string) $this->title;
        } else {
            return 'Unnamed Office';
        }
    }


    /**
     * Converts the description text into HTML paragraphs.
     *
     * This method takes the description text stored in the `$desc` property,
     * splits it into paragraphs based on newline characters, and wraps each
     * non-empty paragraph in `<p>` tags.
     * @return string The HTML representation of the description text.
     */
    public function htmlDesc() {
        $paragraphs = explode("\n", $this->desc);
        $html = '';

        foreach ($paragraphs as $paragraph) {
            $trimmed = trim($paragraph);
            if (!empty($trimmed)) {
                $html .= '<p>' . htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }

        return $html;
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

        // Devolved committee memberships arrive without dates, so say what we
        // know rather than claiming a start in the year 1000.
        if ($this->from_date == self::UNKNOWN_DATE) {
            if ($this->to_date == '9999-12-31') {
                return gettext('current member');
            }
            return sprintf(gettext('until %s'), format_date($this->to_date, SHORTDATEFORMAT));
        }

        if ($this->to_date == '9999-12-31') {
            return 'since ' . format_date($this->from_date, SHORTDATEFORMAT);
        }

        $output = '';

        if (
            !($this->source == 'chgpages/selctee' && $this->from_date == '2004-05-28') and
            !($this->source == 'chgpages/privsec' && $this->from_date == '2004-05-13')
        ) {
            if ($this->source == 'chgpages/privsec' && $this->from_date == '2005-11-10') {
                $output .= 'before ';
            }
            $output .= format_date($this->from_date, SHORTDATEFORMAT) . ' ';
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
