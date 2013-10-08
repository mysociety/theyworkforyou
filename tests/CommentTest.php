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
     * Makes sure the body of the test comment is returned correctly, testing HTML cleaning.
     */
    public function testHTMLCleaningGetBody()
    {
        $comment = new COMMENT(1);
        $this->assertEquals($comment->body(), "This is a test comment, including http://theyworkforyou.com <a href=\"http://theyworkforyou.com\">links</a>, email addresses like test@theyworkforyou.com, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're rendered correctly.

It also spans multiple lines.");
    }

    /**
     * Makes sure a comment is correctly rendered, testing HTML cleaning.
     */
    public function testHTMLCleaningPrepareCommentForDisplay()
    {
        $comment = new COMMENT(1);
        $this->assertEquals(prepare_comment_for_display($comment->body()), "This is a test comment, including <a href=\"http://theyworkforyou.com\" rel=\"nofollow\">http://theyworkforyou.com</a> <a href=\"http://theyworkforyou.com\">links</a>, email addresses like <a href=\"mailto:test@theyworkforyou.com\">test@theyworkforyou.com</a>, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're rendered correctly.<br>
<br>
It also spans multiple lines.");
    }

    /**
     * Tests adding a new comment, testing HTML cleaning.
     */
	public function testHTMLCleaningAddComment()
    {

        global $THEUSER;

        $THEUSER = new THEUSER;

        $THEUSER->init(1);

        $comment = new COMMENT();

        $data = array(
            'epobject_id' => 1,
            'body' => "This is a test comment, including http://theyworkforyou.com <a href=\"http://theyworkforyou.com\">links</a>, <b>bold</b>, <i>italics</i>, and stray < brackets to ensure they're not stripped.

It also includes <script>alert('malicious!');</script> script tags, to ensure they are stripped correctly.

It also spans multiple lines.",
            'gid' => ''
        );

        $commentId = $comment->create($data);

        // A correctly inserted comment returns an integer
        $this->assertInternalType('integer', $commentId);

        $comment = new COMMENT($commentId);

        $this->assertEquals("This is a test comment, including http://theyworkforyou.com <a href=\"http://theyworkforyou.com\">links</a>, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're not stripped.

It also includes  script tags, to ensure they are stripped correctly.

It also spans multiple lines.", $comment->body());

    }

    public function testHTMLCleaningWithNonASCIIChars()
    {
        // this file is UTF-8 but odd comments are sent up looking like Windows-1252 so we need the
        // input text to be encoded thus otherwise the output is different
        $text = iconv('UTF-8', 'Windows-1252', "This is a curly  ’ apostrophe. Is 2 < 3 ø ø €  ’ « ö à");

        $this->assertEquals("This is a curly  &#8217; apostrophe. Is 2 &lt; 3 &#248; &#248; &#8364;  &#8217; &#171; &#246; &#224;", filter_user_input($text, 'comment'));
    }

}
