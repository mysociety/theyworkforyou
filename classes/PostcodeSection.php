<?php

namespace MySociety\TheyWorkForYou;

enum PostcodeSection: string {
    case COMMONS = HouseType::COMMONS->value;
    case SCOTLAND = HouseType::SCOTLAND->value;
    case WALES = HouseType::WALES->value;
    case NI = HouseType::NI->value;
    case COUNCIL = 'council';

    public static function fromHouse(HouseType $house): self {
        return self::from($house->slug());
    }
}
