<?php

namespace Crell\Stacker;


class StringValue
{
    /**
     * @var string
     */
    protected $string;

    /**
     * @var int
     */
    protected $code;

    public function __construct($string, $code = 200)
    {
        $this->string = $string;
        $this->code = $code;
    }

    public function code()
    {
        return $this->code;
    }

    public function __toString()
    {
        return $this->string;
    }
}