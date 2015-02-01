<?php

namespace Crell\Stacker;

use Phly\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A for-reals implementation would use a for-reals event library. This is just to see what listeners look like.
 */
class EventMiddleware implements HttpMiddlewareInterface
{

    /**
     * @var HttpMiddlewareInterface
     */
    protected $inner;

    /**
     * @var array
     */
    protected $listeners;

    public function __construct(HttpMiddlewareInterface $inner)
    {
        $this->inner = $inner;
    }

    public function addRequestListener(callable $listener, $priority = 0)
    {
        $this->listeners['request'][$priority][] = $listener;
    }

    public function addResponseListener(callable $listener, $priority = 0)
    {
        $this->listeners['response'][$priority][] = $listener;
    }

    public function handle(ServerRequestInterface $request)
    {
        $request = $this->fireRequestListeners($request);

        $response = $this->inner->handle($request);

        $response = $this->fireResponseListeners($request, $response);
        return $response;
    }

    protected function fireRequestListeners(ServerRequestInterface $request)
    {
        return $this->fireListeners($request, 'request', ServerRequestInterface::class);
    }

    protected function fireResponseListeners(ServerRequestInterface $request, ResponseInterface $response)
    {
        $priority = $this->listeners['response'];
        ksort($priority);

        foreach ($priority as $listeners) {
            foreach ($listeners as $listener) {
                $ret = $listener($request, $response);
                // Listeners can modify the object by returning a new one, but otherwise
                // cannot change anything.
                // They also cannot short circuit other listeners; if you want to do that,
                // use a middleware instead!
                if ($ret instanceof ResponseInterface) {
                    $response = $ret;
                }
            }
        }
        return $response;
    }

    protected function fireListeners($object, $type, $classType)
    {
        $priority = $this->listeners[$type];
        ksort($priority);

        foreach ($priority as $listeners) {
            foreach ($listeners as $listener) {
                $ret = $listener($object);
                // Listeners can modify the object by returning a new one, but otherwise
                // cannot change anything.
                // They also cannot short circuit other listeners; if you want to do that,
                // use a middleware instead!
                if ($ret instanceof $classType) {
                    $object = $ret;
                }
            }
        }

        return $object;
    }

}
