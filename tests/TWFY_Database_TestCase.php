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
        $dsn = 'mysql:dbname=' . OPTION_TWFY_DB_NAME . ';charset=utf8';
        if (OPTION_TWFY_DB_HOST) {
            $dsn .= ';host=' . OPTION_TWFY_DB_HOST;
        }
        $username = OPTION_TWFY_DB_USER;
        $password = OPTION_TWFY_DB_PASS;
        $pdo = new PDO($dsn, $username, $password);
        $this->db = $pdo;
        return $this->createDefaultDBConnection($pdo, OPTION_TWFY_DB_NAME);
    }

    public function tearDown()
    {
        $this->db = NULL;

        parent::tearDown();
    }
}
