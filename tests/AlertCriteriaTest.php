<?php

use MySociety\TheyWorkForYou\Utility\Alert;

class AlertCriteriaTest extends PHPUnit\Framework\TestCase {
    /**
     * @dataProvider criteriaProvider
     */
    public function testCriteria($details, $expected) {
        $this->assertSame($expected, Alert::detailsToCriteria($details));
    }

    public function criteriaProvider() {
        return [
            [['keyword' => 'A OR B', 'search_section' => 'wales'], '(A OR B) section:wales'],
            [['keyword' => 'A OR B', 'pid' => 123], '(A OR B) speaker:123'],
            [['keyword' => 'A OR B', 'pid' => 123, 'search_section' => 'wales'], '(A OR B) speaker:123 section:wales'],
            [['keyword' => '(A OR B) OR C', 'search_section' => 'wales'], '((A OR B) OR C) section:wales'],
            [['keyword' => '(A OR B) -C', 'search_section' => 'wales'], '((A OR B) -C) section:wales'],
            [['keyword' => 'A B', 'search_section' => 'wales'], 'A B section:wales'],
            [['keyword' => 'A OR B'], 'A OR B'],
            [['pid' => 123], 'speaker:123'],
            [[], ''],
        ];
    }
}
