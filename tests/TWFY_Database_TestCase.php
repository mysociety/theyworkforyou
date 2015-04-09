<?php

/**
 * Provides acceptance(ish) tests for API functions.
 */
abstract class TWFY_Database_TestCase extends PHPUnit_Extensions_Database_TestCase
{
    /**
     * database handle for database queries in tests
     */
    public $db;

    /**
     * Connects to the testing database.
     */
    public function getConnection()
    {
        $dsn = 'mysql:host=' . OPTION_TWFY_DB_HOST . ' ;dbname=' . OPTION_TWFY_DB_NAME;
        $username = OPTION_TWFY_DB_USER;
        $password = OPTION_TWFY_DB_PASS;
        $pdo = new PDO($dsn, $username, $password);
        $this->db = $pdo;
        return $this->createDefaultDBConnection($pdo, OPTION_TWFY_DB_NAME);
    }

}
