<?php

namespace Dimkabelkov\CriteriaHelper\Exception;

use Exception;

class OrderDirectionException extends Exception
{
    public function __construct($order, Exception $previous = null)
    {
        $message = sprintf('Has no `%s` order direction must value `asc` or `desc`', $order);

        parent::__construct($message, $previous);
    }
}
