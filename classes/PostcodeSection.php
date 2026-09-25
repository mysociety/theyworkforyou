<?php

namespace MySociety\TheyWorkForYou;

enum PostcodeSection: string {
    case COMMONS = HouseType::COMMONS->value;
    case SCOTLAND = HouseType::SCOTLAND->value;
    case WALES = HouseType::WALES->value;
    case NI = HouseType::NI->value;

    public static function fromHouse(HouseType $house): self {
        return self::from($house->slug());
    }
}
