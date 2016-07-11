<?php

/**
 * Provides test methods for commenting functionality.
 */
class CommentTest extends TWFY_Database_TestCase
{

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
        $this->assertEquals($comment->body(), "This is a test comment, including https://www.theyworkforyou.com <a href=\"https://www.theyworkforyou.com\">links</a>, email addresses like test@theyworkforyou.com, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're rendered correctly.

It also spans multiple lines.");
    }

    /**
     * Makes sure a comment is correctly rendered, testing HTML cleaning.
     */
    public function testHTMLCleaningPrepareCommentForDisplay()
    {
        $comment = new COMMENT(1);
        $this->assertEquals(prepare_comment_for_display($comment->body()), "This is a test comment, including <a href=\"https://www.theyworkforyou.com\" rel=\"nofollow\">https://www.theyworkforyou.com</a> <a href=\"https://www.theyworkforyou.com\">links</a>, email addresses like <a href=\"mailto:test@theyworkforyou.com\">test@theyworkforyou.com</a>, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're rendered correctly.<br>
<br>
It also spans multiple lines.");
    }

    public function testCommentWithVeryLongLink()
    {
        $comment = new COMMENT(2);
        $this->assertEquals(prepare_comment_for_display($comment->body()),
            '<a href="https://www.theyworkforyou.example.org/this/is/a/coment/with/a/very/long/URL/that/contains/http://something/as/it/is/an/archive" rel="nofollow">https://www.theyworkforyou.example.org/this/is/a/coment/with...</a>');
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
            'body' => "This is a test comment, including https://www.theyworkforyou.com <a href=\"https://www.theyworkforyou.com\">links</a>, <b>bold</b>, <i>italics</i>, and stray < brackets to ensure they're not stripped.

It also includes <script>alert('malicious!');</script> script tags, to ensure they are stripped correctly.

It also spans multiple lines.",
            'gid' => ''
        );

        $commentId = $comment->create($data);

        // A correctly inserted comment returns an integer
        $this->assertInternalType('integer', $commentId);

        $comment = new COMMENT($commentId);

        $this->assertEquals("This is a test comment, including https://www.theyworkforyou.com <a href=\"https://www.theyworkforyou.com\">links</a>, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're not stripped.

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
