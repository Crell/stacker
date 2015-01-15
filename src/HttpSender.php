<?php
/**
 * Created by PhpStorm.
 * User: crell
 * Date: 1/14/15
 * Time: 4:45 PM
 */

namespace Crell\Stacker;


use Psr\Http\Message\ResponseInterface;

class HttpSender
{
    protected $out;

    public function __construct($out = 'php://stdout')
    {
        // @todo Use this later.
        $this->out = $out;
    }


    public function send(ResponseInterface $response)
    {
        $this->sendHeaders($response);
        $this->sendBody($response);

    }

    public function sendHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
    }

    public function sendBody(ResponseInterface $response)
    {
        $body = $response->getBody();

        // @todo Use stream operations to make this more robust and allow
        // writing to an arbitrary stream.
        if ($bytes = $body->getSize() && $bytes < 500) {
            print $body->getContents();
        }
        else {
            while (!$body->eof()) {
                $data = $body->read(1024);
                print $data;
            }
        }
    }
}
