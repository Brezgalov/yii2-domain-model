<?php

namespace Brezgalov\DomainModel\Exceptions;

use Throwable;

class CrossDomainException extends \Exception
{
    /**
     * @var string
     */
    public $originDomain;

    /**
     * @var string
     */
    public $calledDomain;

    /**
     * @param string $originDomain
     * @param string $calledDomain
     * @param string $message
     * @throws CrossDomainException
     */
    public static function throwException($originDomain, $calledDomain, $message)
    {
        $ex = new static();
        $ex->originDomain = $originDomain;
        $ex->calledDomain = $calledDomain;
        $ex->message = $ex->getMessagePrefix() . " {$message}";

        throw $ex;
    }

    /**
     * @return string
     */
    public function getMessagePrefix()
    {
        return "[{$this->originDomain}" . ($this->calledDomain ? " -> {$this->calledDomain}" : '') . "]";
    }
}