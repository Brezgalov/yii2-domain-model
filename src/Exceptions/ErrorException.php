<?php

namespace Brezgalov\DomainModel\Exceptions;

class ErrorException extends \Exception
{
    /**
     * @var int
     */
    public $statusCode = 400;

    /**
     * @var string
     */
    public $errorName;

    /**
     * @var string
     */
    public $error;

    /**
     * @param string $error
     * @param string $statusCode
     * @param string $errorName
     * @throws ErrorException
     */
    public static function throwException($error, $statusCode, $errorName = 'error')
    {
        $ex = new static();
        $ex->statusCode = $statusCode;
        $ex->error = $error;
        $ex->errorName = $errorName;

        throw $ex;
    }
}