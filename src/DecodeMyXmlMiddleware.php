<?php

namespace Crell\Stacker;


use Psr\Http\Message\ServerRequestInterface;

class DecodeMyXmlMiddleware implements HttpMiddlewareInterface
{
    /**
     * @var HttpMiddlewareInterface
     */
    protected $inner;

    public function __construct(HttpMiddlewareInterface $inner)
    {
        $this->inner = $inner;
    }

    public function handle(ServerRequestInterface $request)
    {
        if ($request->getHeader('content-type') == 'application/xml') {
            $content = $request->getBody()->getContents();
            $simplexml = simplexml_load_string($content);
            $data = new MyDomainObject($simplexml);
            $request = $request->withBodyParams(['data' => $data]);
        }
        return $this->inner->handle($request);
    }

}

