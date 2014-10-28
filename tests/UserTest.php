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

    public function testAddUser() {
        $u = new \MySociety\TheyWorkForYou\User();

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
        $u = new \MySociety\TheyWorkForYou\TheUser();
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
        $u = new \MySociety\TheyWorkForYou\TheUser();
        $u->loggedin = 1;

        $this->assertEquals( 'user@example.org', $u->email() );

        $d = $u->update_self( array(
            'firstname' => 'Experiment',
            'lastname' => 'User',
            'email' => 'user@example.com',
            'emailpublic' => '0',
            'postcode' => 'EH1 99SP',
            'password' => '',
            'url' => '',
            'optin' => '',
            'user_id' => 1
        ), false );

        // email should not change as user needs to confirm
        $this->assertEquals( 'user@example.org', $u->email() );

        $tokenCount = $this->getConnection()->getRowCount('tokens', 'data = "1::user@example.com"');
        $this->assertEquals(1, $tokenCount, 'correct number of email confirm tokens');

        // token is based on the time so we can't test for it
        $queryTable = $this->getConnection()->createQueryTable(
            'tokens', 'SELECT type, data FROM tokens WHERE data = "1::user@example.com"'
        );

        $expectedTable = $this->createXmlDataSet(dirname(__FILE__).'/_fixtures/expectedTokens.xml')
                              ->getTable("tokens");
        $this->assertTablesEqual($expectedTable, $queryTable);

        $alertCount = $this->getConnection()->getRowCount('alerts', 'email = "user@example.org"');
        $this->assertEquals(1, $alertCount, 'correct number of alerts');

        $queryTable = $this->getConnection()->createQueryTable(
            'tokens', 'SELECT token, type, data FROM tokens WHERE data = "1::user@example.com"'
        );

        $tokenRow = $queryTable->getRow(0);
        $token = '2-' . $tokenRow['token'];

        $u->confirm_email($token,false);

        $this->assertEquals( 'user@example.com', $u->email(), 'confirming with token updates email address' );
        $tokenCount = $this->getConnection()->getRowCount('tokens', 'data = "1::user@example.com"');
        $this->assertEquals(0, $tokenCount, 'token deleted once email confirmed');

        $alertCount = $this->getConnection()->getRowCount('alerts', 'email = "user@example.com"');
        $this->assertEquals(1, $alertCount, 'one alert for new email address');

        $alertCount = $this->getConnection()->getRowCount('alerts', 'email = "user@example.org"');
        $this->assertEquals(0, $alertCount, 'no alerts for old email address');
    }

    public function testExpiredToken() {
        $_COOKIE['epuser_id'] = '1.5ce7f6e2d7de4db00c297e1da0d48ac';
        $u = new \MySociety\TheyWorkForYou\TheUser();
        $u->loggedin = 1;

        $this->assertEquals( 'user@example.org', $u->email(), 'confirming inital email address' );

        $tokenCount = $this->getConnection()->getRowCount('tokens', 'data = "1::user@example.net"');
        $this->assertEquals(1, $tokenCount, 'correct number of email confirm tokens');

        $token = '2-lkdsjafhsadjhf';

        $u->confirm_email($token,false);
        $this->assertEquals( 'user@example.org', $u->email(), 'expired token does not update email address' );

        $tokenCount = $this->getConnection()->getRowCount('tokens', 'data = "1::user@example.net"');
        $this->assertEquals(1, $tokenCount, 'correct number of email confirm tokens');
    }

}
