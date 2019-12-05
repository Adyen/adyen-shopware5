<?php

namespace MeteorAdyen\Exceptions;

class MissingParameterException extends \Exception
{
    /**
     * MissingParameterException constructor.
     * @param string $parameter
     */
    public function __construct(string $parameter)
    {
        parent::__construct("Missing parameter " . $parameter);
    }
}
