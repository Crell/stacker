<?php

namespace Crell\Stacker;

use Aura\Router\Route;
use Aura\Router\Router;
use Phly\Http\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RoutingMiddleware implements HttpMiddlewareInterface
{
    /**
     * @var HttpMiddlewareInterface
     */
    protected $inner;

    /**
     * @var Router
     */
    protected $router;

    public function __construct(HttpMiddlewareInterface $inner, Router $router)
    {
        $this->inner = $inner;
        $this->router = $router;
    }

    public function handle(ServerRequestInterface $request)
    {
        $path = $request->getUri()->getPath();

        $route = $this->router->match($path, $request->getServerParams());

        return $route
          ? $this->delegate($request, $route)
          : $this->handleFailure($request, $this->router->getFailedRoute());
    }

    /**
     * @param RequestInterface $request
     * @param Route $route
     * @return ResponseInterface
     */
    protected function delegate(ServerRequestInterface $request, Route $route)
    {
        // We can't use setAttributes here, because there MAY already be attributes set.
        foreach ($route->params as $k => $v) {
            // Honestly this feels silly.
            $request = $request->withAttribute($k, $v);
        }
        return $this->inner->handle($request);
    }

    /**
     * @param RequestInterface $request
     * @param Route $failure
     * @return ResponseInterface
     */
    protected function handleFailure(RequestInterface $request, Route $failure)
    {
        // inspect the failed route
        if ($failure->failedMethod()) {
            // the route failed on the allowed HTTP methods.
            // this is a "405 Method Not Allowed" error.
            $response = (new Response(new StringStream('405 Method Not Allowed')))
                ->withStatus(405);
            return $response;

        } elseif ($failure->failedAccept()) {
            // the route failed on the available content-types.
            // this is a "406 Not Acceptable" error.
            $response = (new Response(new StringStream('406 Not Acceptable')))
              ->withStatus(406);
            return $response;
        } else {
            // there was some other unknown matching problem.

            // I'm going to assume it's a 404 for now, just for kicks.
            $response = (new Response(new StringStream('404 Not Found')))
              ->withStatus(404);
            return $response;
        }
    }
}
