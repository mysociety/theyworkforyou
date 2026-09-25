<?php

namespace MySociety\TheyWorkForYou;

/**
 * MapIt area types used for representative and local council lookups.
 */
enum MapItAreaType: string {
    case WMC = 'WMC';
    case SPC = 'SPC';
    case SPCF = 'SPCF';
    case SPE = 'SPE';
    case SPEF = 'SPEF';
    case WAC = 'WAC';
    case WACF = 'WACF';
    case NIE = 'NIE';
    case CTY = 'CTY';
    case DIS = 'DIS';
    case UTA = 'UTA';
    case MTD = 'MTD';
    case LBO = 'LBO';
    case LGD = 'LGD';
    case COI = 'COI';

    public const SCOTLAND_CONSTITUENCIES = [self::SPC, self::SPCF];
    public const SCOTLAND_REGIONS = [self::SPE, self::SPEF];
    public const SCOTLAND = [...self::SCOTLAND_CONSTITUENCIES, ...self::SCOTLAND_REGIONS];
    public const WALES = [self::WAC, self::WACF];
    public const SINGLE_TIER_AUTHORITIES = [self::UTA, self::MTD, self::LBO, self::LGD, self::COI];
    public const LOCAL_AUTHORITIES = [self::CTY, self::DIS, ...self::SINGLE_TIER_AUTHORITIES];

    public function label(): string {
        return match ($this) {
            self::WMC => gettext('Westminster constituency'),
            self::SPC => gettext('Scottish Parliament constituency'),
            self::SPCF => gettext('Future Scottish Parliament constituency'),
            self::SPE => gettext('Scottish Parliament region'),
            self::SPEF => gettext('Future Scottish Parliament region'),
            self::WAC => gettext('Senedd constituency'),
            self::WACF => gettext('Future Senedd constituency'),
            self::NIE => gettext('Northern Ireland Assembly constituency'),
            self::CTY => gettext('County council'),
            self::DIS => gettext('District council'),
            self::UTA => gettext('Unitary authority'),
            self::MTD => gettext('Metropolitan district'),
            self::LBO => gettext('London borough'),
            self::LGD => gettext('Northern Ireland local government district'),
            self::COI => gettext('Council of the Isles of Scilly'),
        };
    }
}
