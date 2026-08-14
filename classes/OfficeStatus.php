<?php

namespace MySociety\TheyWorkForYou;

/**
 * Select current or previous offices.
 */
enum OfficeStatus: string {
    case CURRENT = 'current';
    case PREVIOUS = 'previous';
}
