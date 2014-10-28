<?php
/**
 * WmsList Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\HansardList\WransList;

class WmsList extends \MySociety\TheyWorkForYou\HansardList\WransList {
    public $major = 4;
    public $listpage = 'wms';
    public $commentspage = 'wms';
    public $gidprefix = 'uk.org.publicwhip/wms/';

    public function _get_data_by_recent_wms($args = array()) {
        return $this->_get_data_by_recent_wrans($args);
    }
}
