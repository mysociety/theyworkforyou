<?php

namespace MySociety\TheyWorkForYou;

/**
 * MapIt area types used for representative lookups.
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

    public const SCOTLAND_CONSTITUENCIES = [self::SPC, self::SPCF];
    public const SCOTLAND_REGIONS = [self::SPE, self::SPEF];
    public const SCOTLAND = [...self::SCOTLAND_CONSTITUENCIES, ...self::SCOTLAND_REGIONS];
    public const WALES = [self::WAC, self::WACF];

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
        };
    }
}
