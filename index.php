<?php

require_once 'vendor/autoload.php';

use Aura\Router\Router;
use Aura\Router\RouterFactory;
use Crell\Stacker\BasePathResolverMiddleware;
use Crell\Stacker\CacheMiddleware;
use Crell\Stacker\CallableHttpKernel;
use Crell\Stacker\DispatchingMiddleware;
use Crell\Stacker\EventMiddleware;
use Crell\Stacker\ForbiddenError;
use Crell\Stacker\HttpPathMiddleware;
use Crell\Stacker\HttpSender;
use Crell\Stacker\NegotiationMiddleware;
use Crell\Stacker\NotFoundError;
use Crell\Stacker\RoutingMiddleware;
use Crell\Stacker\StringStream;
use Crell\Stacker\StringValue;
use Crell\Transformer\TransformerBus;
use Phly\Http\Response;
use Phly\Http\ServerRequestFactory;
use Phly\Http\Stream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


$request = ServerRequestFactory::fromGlobals();

// A trivial kernel.
$kernel = new CallableHttpKernel(function (RequestInterface $request) {
    return new Response(new StringStream('Hello World'));
});


// The real middlewares in use are below.
// Note that they are created "inside out", so the final one that handles all requests for realsies
// is the first one defined. Something like StackPHP's builder utiltiy or a DIC would make this nicer.


// The inner-most kernel, which is therefore not really a middleware. It calls
// the actual action/controller callable.
$bus = new TransformerBus(ResponseInterface::class);

$bus->setTransformer(StringValue::class, function (StringValue $string) {
    return new Response(new StringStream($string));
});
$bus->setTransformer(Stream::class, function(Stream $stream) {
    return new Response($stream);
});
$bus->setTransformer(NotFoundError::class, function(NotFoundError $error) {
    return (new Response(new StringStream($error)))->withStatus(404);
});
$bus->setTransformer(ForbiddenError::class, function(ForbiddenError $error) {
    return (new Response(new StringStream($error)))->withStatus(403);
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
$router->add('goodbye', '/goodbye/{name}')
  ->addValues(array(
    'action' => function(RequestInterface $request, $name) {
        return "Goodbye {$name}";
    },
  ));
$router->add('forbidden', '/forbidden')
  ->addValues(array(
    'action' => function() {
        return new ForbiddenError();
    },
  ));
$router->addPost('jsonecho', '/jsonecho')
  ->addValues(array(
    'action' => function(ServerRequestInterface $request) {
        // Just echo the JSON back.
        $params = $request->getBodyParams();
        $encoded = json_encode($params);
        $response = (new Response(new StringStream($encoded)))
          ->withHeader('content-type', 'application/json');
        return $response;
    },
  ));
$kernel = new RoutingMiddleware($kernel, $router);

// Setup pre-routing event listeners.  Remember, order in this file is backwards.
// Putting request and response listeners in the same middleware may not be the
// best idea, but this is just a demo.
$kernel = new EventMiddleware($kernel);
$kernel->addRequestListener(function(ServerRequestInterface $request) {
    return $request->withAttribute('some_silliness', 'myvalue');
});
$kernel->addResponseListener(function(ServerRequestInterface $request, ResponseInterface $response) {
    // This line is seriously ugly. How else can we check "is this an HTML request", though?
    if (in_array('text/html', explode(',', $request->getHeader('accept')))) {
        $content = $response->getBody()->getContents();
        $content .= "<p>My event was here!</p>\n";

        return $response->withBody(new StringStream($content));
    }
});

// Content negotiation, using the Willdurand library.
$kernel = new NegotiationMiddleware($kernel);

// A one-off handler.
$kernel = new HttpPathMiddleware($kernel, '/bye', function(RequestInterface $request) {
    return new Response(new StringStream('Goodbye World'));
});

// The outer-most kernel, strip off a base path.
// In actual usage this would be some derived value or configured or something.
$kernel = new BasePathResolverMiddleware($kernel, '/~crell/stacker');

$kernel = new CacheMiddleware($kernel);

$response = $kernel->handle($request);

$sender = new HttpSender();
$sender->send($response);
