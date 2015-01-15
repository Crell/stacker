<?php

namespace Crell\Stacker;

use Phly\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This is an incredibly stupid and naive caching middleware. Please don't use in production. :-)
 *
 * This makes me really want to have better cache control methods built onto the object.
 * If not in the interface, it could be a source of incompatibility if there are
 * different extensions for it.
 */
class CacheMiddleware implements HttpMiddlewareInterface
{

    /**
     * @var HttpMiddlewareInterface
     */
    protected $inner;

    /**
     * @var array
     */
    protected $totallyStupidCache = [];

    public function __construct(HttpMiddlewareInterface $inner)
    {
        $this->inner = $inner;
    }

    public function handle(ServerRequestInterface $request)
    {
        if ($cachedResponse = $this->getFromCache($request)) {
            return $cachedResponse;
        }

        $response = $this->inner->handle($request);

        $response = $this->setCacheValues($response);

        if ($this->isNotModified($request, $response)) {
            return $response
              ->withStatus(304)
              ->withBody(new StringStream(''))
              ;
        }

        $response = $this->cache($request, $response);
        return $response;
    }

    protected function cache(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->totallyStupidCache[$request->getUri()->getPath()] = $response;
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    protected function isNotModified(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Yeah this could TOTALLY be more robust. :-)

        if ($etag = $request->getHeader('If-none-match')) {
            if ($etag == $response->getHeader('Etag')) {
                  return true;
              }
        }

        return false;
    }

    protected function setCacheValues(ResponseInterface $response)
    {
        $etag = sha1($response->getBody()->getContents());

        // This is technically mutation of the body stream. :-(
        $response->getBody()->rewind();

        $response = $response
          ->withHeader('Etag', $etag)
          // 10 second cache, just enough to show it works.
          ->withHeader('Cache-Control', 'max-age=10, public');

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    protected function getFromCache(ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if (!empty($this->totallyStupidCache[$path])) {
            return $this->totallyStupidCache[$path];
        }

        return null;
    }

}