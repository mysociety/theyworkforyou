<?php

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class SectionTest extends FetchPageTestCase
{

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/section.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page($vars, '', 'section.php');
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
            $this->assertStringContainsString('<div class="calendar">', $page);
            $this->assertStringContainsString('January', $page);
            $this->assertMatchesRegularExpression('/<td colspan="2">&nbsp;<\/td><td[^>]*><a href="\/' . $type . '\/\?d=2014-01-01">1<\/a><\/td><td[^>]*><span>2<\/span><\/td>/', $page);
        }
    }

    public function testDebatesDay() {
        foreach ($this->types as $type) {
            $page = $this->fetch_page( array( 'type' => $type, 'd' => '2014-01-01' ) );
            $this->assertStringContainsString('Wednesday,  1 January 2014', $page);
            $this->assertStringContainsString('HeadingA', $page);
            if ($type == 'wrans') {
                $this->assertStringContainsString('DepartmentA', $page);
                $this->assertStringContainsString('QuestionA', $page);
            } elseif ($type == 'spwrans') {
                $this->assertStringContainsString('QuestionA', $page);
            } elseif ($type == 'wms') {
                $this->assertStringContainsString('DepartmentA', $page);
                $this->assertStringContainsString('StatementA', $page);
            } else {
                $this->assertStringContainsString('SubheadingA', $page);
                $this->assertStringContainsString('SpeechA', $page);
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
                $this->assertMatchesRegularExpression("#All .*?written answers on  1 Jan 2014#i", $page);
                $this->assertStringContainsString("QuestionA", $page);
            } else {
                $this->assertMatchesRegularExpression("#Location: .*?/$type/\?id=2014-01-01b\.1\.2#", $page);
            }
        }
    }

    public function testDebatesSubheading() {
        foreach ($this->types as $type) {
            $page = $this->fetch_page( array( 'type' => $type, 'id' => '2014-01-01b.1.2' ) );
            $this->assertStringContainsString("HeadingA", $page);
            if ($type == 'spwrans') {
                $this->assertStringContainsString("QuestionA", $page);
                $this->assertStringContainsString("AnswerA", $page);
            } elseif ($type == 'wrans') {
                $this->assertStringContainsString("DepartmentA", $page);
                $this->assertStringContainsString("QuestionA", $page);
                $this->assertStringContainsString("AnswerA", $page);
                $this->assertStringContainsString('Mp Mp', $page);
                $this->assertStringContainsString('Highlands and Islands', $page);
                $this->assertStringContainsString('Mp2 Mp2', $page);
                $this->assertStringContainsString('Birmingham', $page);
                $this->assertStringContainsString('Independent', $page);
            } elseif ($type == 'wms') {
                $this->assertStringContainsString('DepartmentA', $page);
                $this->assertStringContainsString('StatementA', $page);
            } else {
                $this->assertStringContainsString("SubheadingA", $page);
                $this->assertStringContainsString("SpeechA", $page);
            }
            $time = strftime('2:30 %p', mktime(14, 30));
            $this->assertStringContainsString($time, $page);
            $this->assertMatchesRegularExpression('#All.*?on  1 Jan 2014#', $page);
            $this->assertStringContainsString("Mp Mp", $page);
        }
    }

    public function testDebatesSpeech() {
        foreach ($this->types as $type) {
            $page = $this->fetch_page( array( 'type' => $type, 'id' => '2014-01-01b.1.3' ) );
            if ($type == 'wrans' || $type == 'spwrans' || $type == 'wms') {
                $this->assertMatchesRegularExpression("#Location: .*?/$type/\?id=2014-01-01b\.1\.2#", $page);
            } else {
                $this->assertStringContainsString("HeadingA", $page);
                $this->assertStringContainsString("SubheadingA", $page);
                $time = strftime('2:30 %p', mktime(14, 30));
                $this->assertStringContainsString($time, $page);
                $this->assertStringContainsString('See the whole debate', $page);
                $this->assertStringContainsString('See this speech in context', $page);
                $this->assertStringContainsString("Mp Mp", $page);
                $this->assertStringContainsString("SpeechA", $page);
            }
        }
    }

    /**
     * Test that applying search highlighting and glossary linking to the same
     * term doesn't break the layout
     *
     * see issue 912 for details
     *
     * @group xapian
     */
    public function testGlossaryAndSearchHighlights() {
            $page = $this->fetch_page( array( 'type' => 'lords', 's' => 'constituency', 'id' => '2014-02-02b.1.3' ) );
            $this->assertStringContainsString("constituency", $page);
            $this->assertStringContainsString("<span class=\"hi\"><a href=\"/glossary/?gl=1\" title=\"In a general election, each Constituency chooses an MP to represent them....\" class=\"glossary\">constituency</a></span>", $page);
    }

    public function testGidRedirect() {
        $page = $this->fetch_page( array( 'type' => 'wrans', 'id' => '2014-01-01a.187335.h' ) );
        $this->assertMatchesRegularExpression("#Location: .*?/wrans/\?id=2014-01-01b\.1\.2#", $page);
    }
}
