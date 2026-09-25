<?php

use MySociety\TheyWorkForYou\HouseType;
use MySociety\TheyWorkForYou\PostcodeSection;
use PHPUnit\Framework\TestCase;

class HouseTypeTest extends TestCase {
    public function testHouseIdsMatchLegacyConstants(): void {
        foreach (HouseType::cases() as $house) {
            $this->assertSame(constant('HOUSE_TYPE_' . $house->name), $house->int_id());
        }
    }

    public function testPostcodeAnchorsUseHouseIdentifiers(): void {
        foreach ([HouseType::COMMONS, HouseType::SCOTLAND, HouseType::WALES, HouseType::NI] as $house) {
            $this->assertSame($house->slug(), PostcodeSection::fromHouse($house)->value);
        }
        $this->assertSame('commons', PostcodeSection::COMMONS->value);
    }

    public function testUnsupportedHouseCannotBecomeAPostcodeSection(): void {
        $this->expectException(ValueError::class);
        PostcodeSection::fromHouse(HouseType::LORDS);
    }
}
