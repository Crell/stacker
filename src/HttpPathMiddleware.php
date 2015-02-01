<?php

namespace Crell\Stacker;


use Psr\Http\Message\ServerRequestInterface;

class HttpPathMiddleware implements HttpMiddlewareInterface
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

    public function __construct(HttpMiddlewareInterface $inner, $path, callable $callable)
    {
        $this->inner = $inner;
        $this->path = $path;
        $this->callable = $callable;
    }

    public function handle(ServerRequestInterface $request)
    {
        if ($request->getUri()->getPath() == $this->path) {
            $call = $this->callable;
            return $call($request);
        }
        else {
            return $this->inner->handle($request);
        }
    }

}

