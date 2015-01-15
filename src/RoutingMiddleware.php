<?php

namespace Crell\Stacker;

use Aura\Router\Route;
use Aura\Router\Router;
use Phly\Http\Response;
use Psr\Http\Message\RequestInterface;
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
        // No, really, this step is total nonsense.
        $parts = parse_url($request->getUrl());
        $path = $parts['path'];

        $route = $this->router->match($path, $request->getServerParams());

        return $route ? $this->delegate($request, $route) : $this->handleFailure($request, $this->router->getFailedRoute());
    }

    protected function delegate(RequestInterface $request, Route $route)
    {
        $request = $request->setAttributes($route->params);
        return $this->inner->handle($request);
    }

    protected function handleFailure(RequestInterface $request, Route $failure)
    {
        // inspect the failed route
        if ($failure->failedMethod()) {
            // the route failed on the allowed HTTP methods.
            // this is a "405 Method Not Allowed" error.
            $response = (new Response(new StringStream('405 Method Not Allowed')))
                ->setStatus(405);
            return $response;

        } elseif ($failure->failedAccept()) {
            // the route failed on the available content-types.
            // this is a "406 Not Acceptable" error.
            $response = (new Response(new StringStream('406 Not Acceptable')))
              ->setStatus(406);
            return $response;
        } else {
            // there was some other unknown matching problem.

            // I'm going to assume it's a 404 for now, just for kicks.
            $response = (new Response(new StringStream('404 Not Found')))
              ->setStatus(404);
            return $response;
        }
    }
}
