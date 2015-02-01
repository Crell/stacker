<?php

namespace Crell\Stacker;


use Phly\Http\Uri;
use Psr\Http\Message\ServerRequestInterface;

class DecodeMyXmlMiddleware implements HttpMiddlewareInterface
{
    /**
     * @var HttpMiddlewareInterface
     */
    protected $inner;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var callable
     */
    protected $callable;

    public function __construct(HttpMiddlewareInterface $inner)
    {
        $this->inner = $inner;
    }

    public function handle(ServerRequestInterface $request)
    {
        //if ($request->getHeader('content-type') == 'application/xml') {
            $content = $request->getBody()->getContents();
            //$simplexml = new \SimpleXMLElement($content);
            $simplexml = new \SimpleXMLElement("<xml></xml>");
            $data = new MyDomainObject($simplexml);
            $request = $request->withBodyParams(['data' => $data]);
       // }

        return $this->inner->handle($request);
    }

}

