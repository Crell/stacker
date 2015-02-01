<?php

namespace Crell\Stacker;


use Phly\Http\Uri;
use Psr\Http\Message\ServerRequestInterface;

class DecodeJsonMiddleware implements HttpMiddlewareInterface
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
        if ($request->getHeader('content-type') == 'application/json') {
            $decoded = json_decode($request->getBody()->getContents());
            $request = $request->withBodyParams($decoded);
        }

        return $this->inner->handle($request);
    }

}

