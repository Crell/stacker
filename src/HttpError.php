<?php

namespace Crell\Stacker;


abstract class HttpError
{
    /**
     * @var string
     */
    protected $message;

    public function __construct($message = null)
    {
        $this->message = $message ?: $this->defaultMessage();
    }

    public function __toString()
    {
        return $this->message;
    }

    protected abstract function defaultMessage();
}
