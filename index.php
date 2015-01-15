<?php

require_once 'vendor/autoload.php';

use Aura\Router\RouterFactory;
use Aura\Router\Router;
use Crell\Stacker\CallableHttpKernel;
use Crell\Stacker\HttpPathMiddleware;
use Crell\Stacker\BasePathResolverMiddleware;
use Crell\Stacker\StringStream;
use Crell\Stacker\StringValue;
use Crell\Stacker\HttpSender;
use Crell\Stacker\RoutingMiddleware;
use Crell\Stacker\DispatchingMiddleware;
use Phly\Http\ServerRequestFactory;
use Phly\Http\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Phly\Http\Stream;

$request = ServerRequestFactory::fromGlobals();

// A trivial kernel.
$kernel = new CallableHttpKernel(function (RequestInterface $request) {
    return new Response(new StringStream('Hello World'));
});

$bus = new \Crell\Transformer\TransformerBus(ResponseInterface::class);

$bus->setTransformer(StringValue::class, function (StringValue $string) {
    return new Response(new StringStream($string));
});
$bus->setTransformer(Stream::class, function(Stream $stream) {
    return new Response($stream);
});

$kernel = new DispatchingMiddleware($bus);

// A routing-based middleware; doesn't actually do anything but the routing resolution.
$router_factory = new RouterFactory;
/** @var Router $router */
$router = $router_factory->newInstance();

$router->add('hello', '/hello/{name}')
  ->addValues(array(
    'action' => function(RequestInterface $request, $name) {
        return new Response(new StringStream("Hello {$name}"));
    },
  ));

$router->add('hello2', '/goodbye/{name}')
  ->addValues(array(
    'action' => function(RequestInterface $request, $name) {
        return "Goodbye {$name}";
    },
  ));

$kernel = new RoutingMiddleware($kernel, $router);

// A one-off handler.
$kernel = new HttpPathMiddleware($kernel, '/bye', function(RequestInterface $request) {
    return new Response(new StringStream('Goodbye World'));
});

// The outer-most kernel, strip off a base path.
// In actual usage this would be some derived value or configured or something.
$kernel = new BasePathResolverMiddleware($kernel, '/~crell/stacker');

$response = $kernel->handle($request);

$sender = new HttpSender();
$sender->send($response);
