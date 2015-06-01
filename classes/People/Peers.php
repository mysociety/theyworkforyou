<?php
/**
 * Peers Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\People;

class Peers extends \MySociety\TheyWorkForYou\People {

    public $type = 'peers';
    public $rep_name = 'Member of the House of Lords';
    public $rep_plural = 'Members of the House of Lords';
    public $house = 2;
    public $subs_missing_image = 'lord';

    protected function getRegionalReps($user) {
        return null;
    }

    protected function getCSVHeaders() {
        return array('Person ID', 'Name', 'Party', 'URI');
    }

    protected function getCSVRow($details) {
        return array(
            $details['person_id'],
            $details['name'],
            $details['party'],
            'http://www.theyworkforyou.com/mp/' . $details['url']
        );
    }
}
