<?php
/**
 * Mirrors pydantic model for deseralisation in a PHP context.
 * For adding display related helper functions.
 * @package TheyWorkForYou
 */

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Statements;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class Signature extends BaseModel {
    public Statement $statement;
    public string $date;
}
