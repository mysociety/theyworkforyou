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
    
    /**
     * Ensures an incorrect email address returns the correct response.
     */
    public function testIsValidIncorrectEmail()
    {
        $user = new THEUSER();

        $this->assertEquals($user->isvalid('incorrect@theyworkforyou.com','incorrectPassword'), array('invalidemail' => 'There is no user registered with an email of ' . htmlentities('incorrect@theyworkforyou.com') . '. If you are subscribed to email alerts, you are not necessarily registered on the website. If you register, you will be able to manage your email alerts, as well as leave annotations.'));
    }
    
    /**
     * Ensures an incorrect password returns the appropriate response.
     */
    public function testIsValidIncorrectPassword()
    {
        $user = new THEUSER();

        $this->assertEquals($user->isvalid('test@theyworkforyou.com','incorrectPassword'), array('invalidpassword' => 'This is not the correct password for ' . htmlentities('test@theyworkforyou.com')));
    }
    
    /**
     * Ensures an incorrect password returns the appropriate response.
     */
    public function testIsValid()
    {
        $user = new THEUSER();

        $this->assertTrue($user->isvalid('test@theyworkforyou.com','Test123%'));
    }
}