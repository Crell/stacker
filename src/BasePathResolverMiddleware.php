<?php

namespace Crell\Stacker;

use Phly\Http\Uri;
use Psr\Http\Message\ServerRequestInterface;

class BasePathResolverMiddleware implements HttpMiddlewareInterface
{

    /**
     * @var HttpMiddlewareInterface
     */
    protected $inner;

    /**
     * @var string
     */
    protected $basePath;

    public function __construct(HttpMiddlewareInterface $inner, $basePath)
    {
        $this->inner = $inner;
        $this->basePath = $basePath;
    }

    public function handle(ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if (strpos($path, $this->basePath) == 0) {
            // This song-and-dance is actually rather annoying.
            $newPath = substr($path, strlen($this->basePath));
            $uri = $uri->withPath($newPath);
            $request = $request->withUri($uri);
        }

        return $this->inner->handle($request);
    }

}