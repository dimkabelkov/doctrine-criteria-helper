<?php

namespace Dimkabelkov\CriteriaHelper\Exception;

use Exception;

class InvalidComparisonException extends Exception
{
    public function __construct($operator, Exception $previous = null)
    {
        $message = sprintf('Has no `%s` comparision, must be `eq`, `neq`, `lt`, `lte`, `gt`, `gte`, `in`, `notIn`, `like` or `not like` ', $operator);

        parent::__construct($message, $previous);
    }
}
