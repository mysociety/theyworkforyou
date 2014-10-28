<?php
/**
 * ParlDb Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Connect to the TheyWorkForYou database.
 *
 * Creates a MySQL object which is connected to the main TheyWorkForYou database.
 *
 * @todo At some point all database connectivity should be handled through DI.
 */

class ParlDb extends MySql {

    public function __construct() {
        $this->init (OPTION_TWFY_DB_HOST, OPTION_TWFY_DB_USER, OPTION_TWFY_DB_PASS, OPTION_TWFY_DB_NAME);
    }

}
