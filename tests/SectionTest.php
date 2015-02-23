<?php

include_once 'FetchPageTestCase.php';

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class SectionTest extends FetchPageTestCase
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

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/section.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page('', $vars, 'www/docs', 'section.php');
    }

    var $types = array('debates', 'whall', 'wrans', 'wms', 'ni', 'sp', 'spwrans', 'lords');

	public function testDebatesFront() {
        foreach ($this->types as $type) {
            $this->fetch_page( array( 'type' => $type ) );
        }
    }

	public function testDebatesYear() {
        foreach ($this->types as $type) {
            $page = $this->fetch_page( array( 'type' => $type, 'y' => '2014' ) );
            $this->assertContains('<div class="calendar">', $page);
            $this->assertContains('January 2014', $page);
            $this->assertContains('<tr><td colspan="2">&nbsp;</td><td><a href="/' . $type . '/?d=2014-01-01">1</a></td><td><span>2</span></td>', $page);
        }
    }

	public function testDebatesDay() {
        foreach ($this->types as $type) {
            $page = $this->fetch_page( array( 'type' => $type, 'd' => '2014-01-01' ) );
            $this->assertContains('Wednesday, 1 January 2014', $page);
            $this->assertContains('HeadingA', $page);
            if ($type == 'wrans') {
                $this->assertContains('DepartmentA', $page);
                $this->assertContains('QuestionA', $page);
            } elseif ($type == 'spwrans') {
                $this->assertContains('QuestionA', $page);
            } elseif ($type == 'wms') {
                $this->assertContains('DepartmentA', $page);
                $this->assertContains('StatementA', $page);
            } else {
                $this->assertContains('SubheadingA', $page);
                $this->assertContains('SpeechA', $page);
            }
        }
    }

	public function testDebatesHeading() {
        foreach ($this->types as $type) {
            if ($type == 'spwrans') {
                # Only one level of headings on spwrans
                continue;
            }

            $page = $this->fetch_page( array( 'type' => $type, 'id' => '2014-01-01b.1.1' ) );
            if ($type == 'wrans') {
                $this->assertRegexp("#All .*?written answers on 1 Jan 2014#i", $page);
                $this->assertContains("QuestionA", $page);
            } else {
                $this->assertRegexp("#Location: .*?/$type/\?id=2014-01-01b\.1\.2#", $page);
            }
        }
    }

	public function testDebatesSubheading() {
        foreach ($this->types as $type) {
            $page = $this->fetch_page( array( 'type' => $type, 'id' => '2014-01-01b.1.2' ) );
            $this->assertContains("HeadingA", $page);
            if ($type == 'spwrans') {
                $this->assertContains("QuestionA", $page);
                $this->assertContains("AnswerA", $page);
            } elseif ($type == 'wrans') {
                $this->assertContains("DepartmentA", $page);
                $this->assertContains("QuestionA", $page);
                $this->assertContains("AnswerA", $page);
            } elseif ($type == 'wms') {
                $this->assertContains('DepartmentA', $page);
                $this->assertContains('StatementA', $page);
            } else {
                $this->assertContains("SubheadingA", $page);
                $this->assertContains("SpeechA", $page);
            }
            $this->assertContains("2:30 pm", $page);
            $this->assertRegexp('#All.*?on 1 Jan 2014#', $page);
            $this->assertContains("Mp Mp", $page);
        }
    }

	public function testDebatesSpeech() {
        foreach ($this->types as $type) {
            $page = $this->fetch_page( array( 'type' => $type, 'id' => '2014-01-01b.1.3' ) );
            if ($type == 'wrans' || $type == 'spwrans' || $type == 'wms') {
                $this->assertRegexp("#Location: .*?/$type/\?id=2014-01-01b\.1\.2#", $page);
            } else {
                $this->assertContains("HeadingA", $page);
                $this->assertContains("SubheadingA", $page);
                $this->assertContains("2:30 pm", $page);
                $this->assertContains('See the whole debate', $page);
                $this->assertContains('See this speech in context', $page);
                $this->assertContains("Mp Mp", $page);
                $this->assertContains("SpeechA", $page);
            }
        }
    }

    public function testGidRedirect() {
        $page = $this->fetch_page( array( 'type' => 'wrans', 'id' => '2014-01-01a.187335.h' ) );
        $this->assertRegexp("#Location: .*?/wrans/\?id=2014-01-01b\.1\.2#", $page);
    }
}
