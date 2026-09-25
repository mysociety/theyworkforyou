<?php

namespace MySociety\TheyWorkForYou;

/**
 * Canonical house identifiers and their legacy database IDs.
 */
enum HouseType: string {
    case ROYAL = 'royal';
    case COMMONS = 'commons';
    case LORDS = 'lords';
    case NI = 'ni';
    case SCOTLAND = 'scotland';
    case WALES = 'senedd';
    case LONDON_ASSEMBLY = 'london-assembly';

    public function slug(): string {
        return $this->value;
    }

    public function int_id(): int {
        return match ($this) {
            self::ROYAL => HOUSE_TYPE_ROYAL,
            self::COMMONS => HOUSE_TYPE_COMMONS,
            self::LORDS => HOUSE_TYPE_LORDS,
            self::NI => HOUSE_TYPE_NI,
            self::SCOTLAND => HOUSE_TYPE_SCOTLAND,
            self::WALES => HOUSE_TYPE_WALES,
            self::LONDON_ASSEMBLY => HOUSE_TYPE_LONDON_ASSEMBLY,
        };
    }
}
