<?php

/**
 * Provides test methods for user functionality.
 */
class UserTest extends PHPUnit_Extensions_Database_TestCase
{

    /**
     * Connects to the testing database.
     */
    public function getConnection()
    {
        $dsn = 'mysql:host=' . OPTION_TWFY_DB_HOST . ' ;dbname=' . OPTION_TWFY_DB_NAME;
        $username = OPTION_TWFY_DB_USER;
        $password = OPTION_TWFY_DB_PASS;
        $pdo = new PDO($dsn, $username, $password);
        return $this->createDefaultDBConnection($pdo, OPTION_TWFY_DB_NAME);
    }

    /**
     * Loads the user testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/user.xml');
    }

    /**
     * Ensures the database is prepared and the user class is included for every test.
     */
    public function setUp()
    {
        parent::setUp();

        include_once('www/includes/easyparliament/user.php');
    }

    public function testAddUser() {
        $u = new USER();

        $details = array(
            "firstname" => 'Test',
            "lastname" => 'User',
            "email" => 'test@example.org',
            "emailpublic" => '0',
            "postcode" => 'EH1 99SP',
            "mp_alert" => false,
            "url" => '',
            "password" => '',
            "optin" => '0',
            "status" => 'User'
        );
        $u->add($details, false);

        $id = $u->user_id();
        $u->init($id);

        $this->assertEquals( 'Test', $u->firstname() );
        $this->assertEquals( 'EH1 99SP', $u->postcode() );
    }

    public function testEditUser() {
        $_COOKIE['epuser_id'] = '1.5ce7f6e2d7de4db00c297e1da0d48ac';
        $u = new THEUSER();
        $u->loggedin = 1;

        $this->assertEquals( 'Test', $u->firstname() );

        $d = $u->update_self( array(
            'firstname' => 'Experiment',
            'lastname' => 'User',
            'emailpublic' => '0',
            'postcode' => 'EH1 99SP',
            'password' => '',
            'url' => '',
            'optin' => '',
            'user_id' => 1
        ) );

        $this->assertEquals( 'Experiment', $u->firstname() );
    }

    public function testEditUserEmail() {
        $_COOKIE['epuser_id'] = '1.5ce7f6e2d7de4db00c297e1da0d48ac';
        $u = new THEUSER();
        $u->loggedin = 1;

        $this->assertEquals( 'user@example.org', $u->email() );

        $d = $u->_update( array(
            'firstname' => 'Experiment',
            'lastname' => 'User',
            'emailpublic' => '0',
            'postcode' => 'EH1 99SP',
            'password' => '',
            'url' => '',
            'optin' => '',
            'user_id' => 1
        ) );
    }

}
