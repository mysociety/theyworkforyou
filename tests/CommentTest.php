<?php

/**
 * Provides test methods for commenting functionality.
 */
class CommentTest extends PHPUnit_Extensions_Database_TestCase
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
     * Loads the comments testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/comment.xml');
    }

    /**
     * Ensures the database is prepared and the comment class is included for every test.
     */
	public function setUp() {
	
        parent::setUp();
        
        include_once('www/includes/easyparliament/comment.php');
    }

    /**
     * Makes sure the body of the test comment is returned correctly.
     */
    public function testGetBody()
    {
        $comment = new COMMENT(1);
        $this->assertEquals($comment->body(), 'This is a test comment, featuring a link to http://theyworkforyou.com and an email address of test@theyworkforyou.com.

It also spans multiple lines.');
    }

    /**
     * Tests adding a new comment.
     */
	public function testAddComment()
    {
    
        global $THEUSER;
    
        $THEUSER = new THEUSER;
        
        $THEUSER->init(1);
    
        $comment = new COMMENT();
        
        $data = array(
            'epobject_id' => 1,
            'body' => "This is a test comment, including <a href=\"#\">links</a> and apostrophes to ensure they're not stripped.

It also spans multiple lines.",
            'gid' => ''
        );
        
        $commentId = $comment->create($data);
        
        // A correctly inserted comment returns an integer
        $this->assertInternalType('integer', $commentId);
        
        $comment = new COMMENT($commentId);
        
        $this->assertEquals($comment->body(), "This is a test comment, including <a href=\"#\">links</a> and apostrophes to ensure they're not stripped.

It also spans multiple lines.");

    }
}