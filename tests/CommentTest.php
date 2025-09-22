<?php

/**
 * Provides test methods for commenting functionality.
 */
class CommentTest extends FetchPageTestCase {
    /**
     * Loads the comments testing fixture.
     */
    public function getDataSet() {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/comment.xml');
    }

    private function fetch_page($vars) {
        return $this->base_fetch_page($vars, '', 'section.php');
    }

    /**
     * Ensures the database is prepared and the comment class is included for every test.
     */
    public function setUp(): void {

        parent::setUp();

        include_once('www/includes/easyparliament/comment.php');
    }

    /**
     * Makes sure the body of the test comment is returned correctly, testing HTML cleaning.
     */
    public function testHTMLCleaningGetBody() {
        $comment = new COMMENT(1);
        $this->assertEquals($comment->body(), "This is a test comment, including https://www.theyworkforyou.com <a href=\"https://www.theyworkforyou.com\">links</a>, email addresses like test@theyworkforyou.com, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're rendered correctly.

It also spans multiple lines.");
    }

    /**
     * Makes sure a comment is correctly rendered, testing HTML cleaning.
     * As we're now doing markdown we don't do this anymore
    public function testHTMLCleaningPrepareCommentForDisplay() {
        $comment = new COMMENT(1);
        $this->assertEquals(prepare_comment_for_display($comment->body()), "<p>This is a test comment, including <a href=\"https://www.theyworkforyou.com\" rel=\"nofollow\">https://www.theyworkforyou.com</a> <a href=\"https://www.theyworkforyou.com\">links</a>, email addresses like <a href=\"mailto:test@theyworkforyou.com\">test@theyworkforyou.com</a>, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're rendered correctly.</p>
<p>It also spans multiple lines.</p>");
    }
     */

    public function testCommentWithVeryLongLink() {
        $comment = new COMMENT(2);
        $this->assertEquals(
            prepare_comment_for_display($comment->body()),
            '<p><a href="https://www.theyworkforyou.example.org/this/is/a/coment/with/a/very/long/URL/that/contains/http://something/as/it/is/an/archive" rel="nofollow">https://www.theyworkforyou.example.org/this/is/a/coment/with...</a></p>'
        );
    }

    public function testMarkdownInComments() {
        $comment = new COMMENT(3);
        $this->assertEquals(
            prepare_comment_for_display($comment->body()),
            '<p>This is a comment with <strong>bold</strong> and <a href="https://www.theyworkforyou.com" rel="nofollow">a link</a>.</p>'
        );
    }

    public function testAddCommentPermissions() {

        global $THEUSER;

        $THEUSER = new THEUSER();

        $THEUSER->init(2);

        $comment = new COMMENT();

        $data = [
            'epobject_id' => 1,
            'body' => "This is a test comment, including https://www.theyworkforyou.com <a href=\"https://www.theyworkforyou.com\">links</a>, <b>bold</b>, <i>italics</i>, and stray < brackets to ensure they're not stripped.

It also includes <script>alert('malicious!');</script> script tags, to ensure they are stripped correctly.

It also spans multiple lines.",
            'gid' => '',
        ];

        $commentId = $comment->create($data);
        $this->assertFalse($commentId);
    }

    /**
     * Tests adding a new comment, testing HTML cleaning.
     */
    public function testHTMLCleaningAddComment() {

        global $THEUSER;

        $THEUSER = new THEUSER();

        $THEUSER->init(1);

        $comment = new COMMENT();

        $data = [
            'epobject_id' => 1,
            'body' => "This is a test comment, including https://www.theyworkforyou.com <a href=\"https://www.theyworkforyou.com\">links</a>, <b>bold</b>, <i>italics</i>, and stray < brackets to ensure they're not stripped.

It also includes <script>alert('malicious!');</script> script tags, to ensure they are stripped correctly.

It also spans multiple lines.",
            'gid' => '',
        ];

        $commentId = $comment->create($data);

        // A correctly inserted comment returns an integer
        $this->assertIsInt($commentId);

        $comment = new COMMENT($commentId);

        $this->assertEquals("This is a test comment, including https://www.theyworkforyou.com <a href=\"https://www.theyworkforyou.com\">links</a>, <b>bold</b>, <i>italics</i>, and stray &lt; brackets to ensure they're not stripped.

It also includes alert('malicious!'); script tags, to ensure they are stripped correctly.

It also spans multiple lines.", $comment->body());

    }

    public function testCommentsFromNoCommentPermissionUserNotShown() {
        global $THEUSER;

        $THEUSER = new THEUSER();

        $THEUSER->init(1);

        $comment = new COMMENT();

        $data = [
            'epobject_id' => 603,
            'body' => "This is a test comment that should not be displayed as the user doesn't have permissions",
            'gid' => '',
        ];

        $commentId = $comment->create($data);

        $page = $this->fetch_page([ 'type' => 'debates', 'id' => '2014-01-01b.1.2' ]);
        $this->assertStringContainsString('This is a...', $page);

        $THEUSER->_update([
            'user_id' => 1,
            'firstname' => $THEUSER->firstname,
            'lastname' => $THEUSER->lastname,
            'postcode' => $THEUSER->postcode,
            'url' => $THEUSER->url,
            'optin' => $THEUSER->optin,
            'can_annotate' => 0,
            'organisation' => '',
        ]);

        $page = $this->fetch_page([ 'type' => 'debates', 'id' => '2014-01-01b.1.2' ]);
        $this->assertStringNotContainsString('This is a...', $page);
    }

    public function testOldCommentsShown() {
        global $THEUSER;

        $THEUSER = new THEUSER();

        $THEUSER->init(1);

        $comment = new COMMENT();

        $data = [
            'epobject_id' => 603,
            'body' => "This is a test comment that should be displayed as it is old",
            'gid' => '',
        ];

        $commentId = $comment->create($data);

        self::$db->query("UPDATE comments SET user_id = 2 WHERE comment_id = $commentId");

        $page = $this->fetch_page([ 'type' => 'debates', 'id' => '2014-01-01b.1.2' ]);
        $this->assertStringNotContainsString('This is a...', $page);

        self::$db->query("UPDATE comments SET posted = '2024-10-09 12:42:11' WHERE comment_id = $commentId");

        $page = $this->fetch_page([ 'type' => 'debates', 'id' => '2014-01-01b.1.2' ]);
        $this->assertStringContainsString('This is a...', $page);
    }

    public function testHTMLCleaningOfAngleBrackets() {
        $text = 'Is 2 < 3?';

        $this->assertEquals('Is 2 &lt; 3?', filter_user_input($text, 'comment'));
    }

    public function testHTMLCleaningWithNonASCIIChars() {
        // everything is UTF-8 so we don't need to encode
        $text = "This is a curly  ’ apostrophe. Is 2 &lt; 3 ø ø €  ’ « ö à";

        $this->assertEquals("<p>This is a curly  ’ apostrophe. Is 2 &lt; 3 ø ø €  ’ « ö à</p>", prepare_comment_for_display($text));
    }

}
