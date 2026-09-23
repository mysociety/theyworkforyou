<?php

namespace MySociety\TheyWorkForYou;

/**
 * Categories stored in moffice.post_type.
 */
enum PostType: string {
    case COMMITTEE = 'committee';
    case GOVERNMENT = 'government';
    case OPPOSITION = 'opposition';
    case PARLIAMENTARY = 'parliamentary';
    case OTHER = 'other';
}
