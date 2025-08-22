<?php
/**
 * Classes to manage a very basic 'Group' format (which can take key properties of committees, APPGs)
 * @package TheyWorkForYou
 */

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Groups;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class MiniGroup extends BaseModel {
    public string $slug = "";
    public string $name = "";
    public string $description = "";
    public string $external_url = "";
    public string $group_type = "";
    public MiniGroupCategoryList $group_categories;
    public MiniMemberList $members;
}
