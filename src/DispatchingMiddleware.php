<?php

namespace Crell\Stacker;

use Crell\Transformer\TransformerBusInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class DispatchingMiddleware implements HttpMiddlewareInterface
{
    /**
     * @var TransformerBusInterface
     */
    protected $responderBus;

    public function __construct(TransformerBusInterface $responderBus)
    {
        $this->responderBus = $responderBus;
    }

    public function handle(ServerRequestInterface $request)
    {
        $action = $request->getAttribute('action');

        $arguments = $this->getArguments($request, $request->getAttributes(), $action);

        $result = call_user_func_array($action, $arguments);

        if (is_string($result)) {
            $result = new StringValue($result);
        }
        else if (is_array($result)) {
            $result = new \ArrayObject($result);
        }

        return $this->responderBus->transform($result);
    }

    // These two functions are ripped *almost* directly from Symfony HttpKernel.

    public function getArguments(RequestInterface $request, $candidates, $action)
    {
        if (is_array($action)) {
            $r = new \ReflectionMethod($action[0], $action[1]);
        } elseif (is_object($action) && !$action instanceof \Closure) {
            $r = new \ReflectionObject($action);
            $r = $r->getMethod('__invoke');
        } else {
            $r = new \ReflectionFunction($action);
        }
        return $this->doGetArguments($request, $candidates, $action, $r->getParameters());
    }

    protected function doGetArguments(RequestInterface $request, array $candidates, $action, array $parameters)
    {
        $arguments = array();
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $candidates)) {
                $arguments[] = $candidates[$param->name];
            } elseif ($param->getClass() && $param->getClass()->isInstance($request)) {
                $arguments[] = $request;
            } elseif ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                if (is_array($action)) {
                    $repr = sprintf('%s::%s()', get_class($action[0]), $action[1]);
                } elseif (is_object($action)) {
                    $repr = get_class($action);
                } else {
                    $repr = $action;
                }
                throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name));
            }
        }
        return $arguments;
    }
}