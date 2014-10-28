<?php

/**
 * Provides test methods for commenting functionality.
 */
class CommentTest extends PHPUnit_Extensions_Database_TestCase
{

    private $page;
    private $the_user;
    private $hansard_list;

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
     * Ensures there is a Page object available for testing.
     */
    public function setUp()
    {
        parent::setUp();

        $this->the_user = new \MySociety\TheyWorkForYou\TheUser();
        $this->page = new \MySociety\TheyWorkForYou\Page();

        global $hansardmajors;

        $this->hansard_majors = $hansardmajors;
    }


    /**
     * Loads the comments testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/comment.xml');
    }

    /**
     * Makes sure the body of the test comment is returned correctly, testing HTML cleaning.
     */
    public function testHTMLCleaningGetBody()
    {
        $comment = new \MySociety\TheyWorkForYou\Comment($this->the_user, $this->page, $this->hansard_majors, 1);
        $this->assertEquals($comment->body(), "This is a test comment, including http://theyworkforyou.com <a href=\"http://theyworkforyou.com\">links</a>, email addresses like test@theyworkforyou.com, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're rendered correctly.

It also spans multiple lines.");
    }

    /**
     * Makes sure a comment is correctly rendered, testing HTML cleaning.
     */
    public function testHTMLCleaningPrepareCommentForDisplay()
    {
        $comment = new \MySociety\TheyWorkForYou\Comment($this->the_user, $this->page, $this->hansard_majors, 1);
        $this->assertEquals(prepare_comment_for_display($comment->body()), "This is a test comment, including <a href=\"http://theyworkforyou.com\" rel=\"nofollow\">http://theyworkforyou.com</a> <a href=\"http://theyworkforyou.com\">links</a>, email addresses like <a href=\"mailto:test@theyworkforyou.com\">test@theyworkforyou.com</a>, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're rendered correctly.<br>
<br>
It also spans multiple lines.");
    }

    /**
     * Tests adding a new comment, testing HTML cleaning.
     */
	public function testHTMLCleaningAddComment()
    {

        $THEUSER = new \MySociety\TheyWorkForYou\TheUser;

        $THEUSER->init(1);

        $comment = new \MySociety\TheyWorkForYou\Comment($THEUSER, $this->page, $this->hansard_majors);

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

        $comment = new \MySociety\TheyWorkForYou\Comment($THEUSER, $this->page, $this->hansard_majors, $commentId);

        $this->assertEquals("This is a test comment, including http://theyworkforyou.com <a href=\"http://theyworkforyou.com\">links</a>, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're not stripped.

It also includes alert('malicious!'); script tags, to ensure they are stripped correctly.

It also spans multiple lines.", $comment->body());

    }

    public function testHTMLCleaningOfAngleBrackets() {
        $text = 'Is 2 < 3?';

        $this->assertEquals('Is 2 &lt; 3?', filter_user_input( $text, 'comment' ) );
    }

    public function testHTMLCleaningWithNonASCIIChars()
    {
        // this file is UTF-8 but odd comments are sent up looking like Windows-1252 so we need the
        // input text to be encoded thus otherwise the output is different
        $text = iconv('UTF-8', 'Windows-1252', "This is a curly  ’ apostrophe. Is 2 &lt; 3 ø ø €  ’ « ö à");

        $this->assertEquals("This is a curly  &rsquo; apostrophe. Is 2 &lt; 3 &oslash; &oslash; &euro;  &rsquo; &laquo; &ouml; &agrave;", prepare_comment_for_display($text));
    }

}
