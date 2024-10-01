<?php
/**
 * MSs Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\People;

class MSs extends \MySociety\TheyWorkForYou\People {
    public $type = 'mss';
    public $house = HOUSE_TYPE_WALES;
    public $cons_type = 'WAC';
    public $reg_cons_type = 'WAE';

    public function __construct() {
        if (LANGUAGE == 'cy') {
            $this->rep_name = 'AS';
            $this->rep_plural = 'ASau';
        } else {
            $this->rep_name = 'MS';
            $this->rep_plural = 'MSs';
        }
        parent::__construct();
    }
}
