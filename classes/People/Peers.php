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
    public $house = HOUSE_TYPE_LORDS;
    public $subs_missing_image = 'lord';

    protected function getRegionalReps($user) {
        return null;
    }

    protected function getCSVHeaders() {
        return ['Person ID', 'Name', 'Party', 'URI'];
    }

    protected function getCSVRow($details) {
        return [
            $details['person_id'],
            $details['name'],
            $details['party'],
            'https://www.theyworkforyou.com/peer/' . $details['url'],
        ];
    }
}
