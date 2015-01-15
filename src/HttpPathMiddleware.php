<?php

namespace Crell\Stacker;


use Phly\Http\Uri;
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
        // No, really, this step is total nonsense.
        $uri = new Uri($request->getUrl());

        $parts = parse_url($uri);

        $path = $parts['path'];

        //var_dump($uri);
        //var_dump($this->path);

        if ($path == $this->path) {
            $call = $this->callable;
            return $call($request);
        }
        else {
            return $this->inner->handle($request);
        }
    }

}

