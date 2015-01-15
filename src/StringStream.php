<?php
/**
 * Created by PhpStorm.
 * User: crell
 * Date: 1/14/15
 * Time: 5:18 PM
 */

namespace Crell\Stacker;


use Phly\Http\Stream;

class StringStream extends Stream
{

    public function __construct($string)
    {
        $stream = 'php://temp';
        parent::__construct($stream, 'r+');

        fwrite($this->resource, $string);
        fseek($this->resource, 0);
    }
}
