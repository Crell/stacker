<?php

require_once 'vendor/autoload.php';

use Phly\Http\ServerRequestFactory;
use Phly\Http\Stream;
use Crell\Stacker\CallableHttpKernel;
use Crell\Stacker\HttpPathMiddleware;
use Phly\Http\Response;
use Psr\Http\Message\RequestInterface;
use Crell\Stacker\StringStream;

$request = ServerRequestFactory::fromGlobals();

$kernel = new CallableHttpKernel(function (RequestInterface $request) {
    return new Response(new StringStream('Hello World'));
});

$kernel = new HttpPathMiddleware($kernel, '/bye', function(RequestInterface $request) {
    return new Response(new StringStream('Goodbye World'));
});

$kernel = new \Crell\Stacker\BasePathResolverMiddleware($kernel, '/~crell/stacker');

$response = $kernel->handle($request);

$sender = new \Crell\Stacker\HttpSender();
$sender->send($response);
