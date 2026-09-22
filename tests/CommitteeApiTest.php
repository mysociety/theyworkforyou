<?php

/**
 * Exercise public API dispatch and SQL against the dedicated test database.
 */
class CommitteeApiTest extends FetchPageTestCase {
    public function getDataSet() {
        return $this->createMySQLXMLDataSet(__DIR__ . '/_fixtures/api.xml');
    }

    public function setUp(): void {
        parent::setUp();
        self::$db->exec('DELETE FROM moffice');
        $houses = ['uk' => HOUSE_TYPE_COMMONS, 'scotland' => HOUSE_TYPE_SCOTLAND,
            'wales' => HOUSE_TYPE_WALES, 'northern-ireland' => HOUSE_TYPE_NI];
        foreach ($houses as $parliament => $house) {
            $person = 2000 + $house;
            $member = self::$db->prepare('INSERT INTO member (member_id, person_id, house) VALUES (?, ?, ?)');
            $member->execute([$person, $person, $house]);
            $name = self::$db->prepare("INSERT INTO person_names (person_id, given_name, family_name) VALUES (?, 'Test', ?)");
            $name->execute([$person, $parliament]);
            $office = self::$db->prepare("INSERT INTO moffice
                (moffice_id, org_id, person, dept, position, source, parliament, post_type)
                VALUES (?, ?, ?, ?, 'Chairman', '', ?, 'committee')");
            $office->execute([$parliament, 'org-' . $parliament, $person, 'Health Committee', $parliament]);
        }
        // A second house and overlapping membership must not duplicate results.
        self::$db->exec('INSERT INTO member (member_id, person_id, house) VALUES (9001, 2001, 2), (9002, 2001, 1)');
        self::$db->exec("INSERT INTO moffice
            (moffice_id, org_id, person, dept, position, source, parliament, post_type)
            VALUES ('ni-prefix', 'ni-prefix', 2003, 'Committee for Justice', 'Member', '', 'northern-ireland', 'committee'),
            ('other-role', NULL, 2001, 'Other Committee', 'Minister', '', 'uk', 'government')");
    }

    private function fetchCommittee(array $parameters = []): array {
        $page = $this->base_fetch_page($parameters + [
            'method' => 'getCommittee', 'key' => 'test_key', 'output' => 'json',
        ], 'api');
        $result = json_decode($page, true);
        $this->assertIsArray($result, $page);
        return $result;
    }

    public function testDefaultRemainsUKAndDoesNotDuplicateMembers(): void {
        $implicit = $this->fetchCommittee(['name' => 'Health']);
        $this->assertSame($implicit, $this->fetchCommittee(['name' => 'Health', 'parliament' => 'uk']));
        $this->assertSame($implicit, $this->fetchCommittee(['name' => 'Health', 'parliament' => '']));
        $this->assertSame('Health Committee', $implicit['committee']);
        $this->assertCount(1, $implicit['members']);
        $this->assertSame('Test uk', $implicit['members'][0]['name']);
        $this->assertSame('Chairman', $implicit['members'][0]['position']);
        $this->assertSame(['committees' => [['name' => 'Health Committee']]], $this->fetchCommittee());
    }

    public function testEachParliamentRestrictsListsAndMembers(): void {
        foreach (['uk', 'scotland', 'wales', 'northern-ireland'] as $parliament) {
            $result = $this->fetchCommittee(['name' => 'Health', 'parliament' => $parliament]);
            $this->assertCount(1, $result['members']);
            $this->assertSame('Test ' . $parliament, $result['members'][0]['name']);
            $list = $this->fetchCommittee(['parliament' => $parliament]);
            $this->assertCount($parliament === 'northern-ireland' ? 2 : 1, $list['committees']);
        }
        $result = $this->fetchCommittee(['name' => 'Justice', 'parliament' => 'northern-ireland']);
        $this->assertSame('Committee for Justice', $result['committee']);
        $this->assertCount(1, $result['members']);
    }

    public function testInvalidParliamentOnBothPaths(): void {
        foreach (['invalid', '0'] as $parliament) {
            foreach ([[], ['name' => 'Health']] as $parameters) {
                $result = $this->fetchCommittee($parameters + ['parliament' => $parliament]);
                $this->assertStringContainsString('Unknown parliament', $result['error']);
            }
        }
    }

    public function testHistoricalMembershipAndAmbiguousNames(): void {
        self::$db->exec("UPDATE moffice SET from_date = '2010-01-01', to_date = '2015-12-31' WHERE parliament = 'wales'");
        $this->assertArrayHasKey('error', $this->fetchCommittee(['parliament' => 'wales']));
        $this->assertCount(1, $this->fetchCommittee(['parliament' => 'wales', 'date' => '2012-01-01'])['committees']);
        $this->assertCount(1, $this->fetchCommittee(['parliament' => 'wales', 'name' => 'Health', 'date' => '2012-01-01'])['members']);
        self::$db->exec("INSERT INTO moffice
            (moffice_id, org_id, person, dept, source, parliament, post_type)
            VALUES ('second', 'org-second', 2001, 'Health Scrutiny Committee', '', 'uk', 'committee')");
        $this->assertCount(2, $this->fetchCommittee(['name' => 'Health'])['committees']);
        $this->assertCount(1, $this->fetchCommittee(['name' => 'Health Scrutiny'])['members']);
    }

    public function testMemberMustBelongToSelectedParliament(): void {
        self::$db->exec("UPDATE moffice SET person = 2001 WHERE parliament = 'wales'");
        $result = $this->fetchCommittee(['name' => 'Health', 'parliament' => 'wales']);
        $this->assertSame('That committee has no members...?', $result['error']);
    }
}
