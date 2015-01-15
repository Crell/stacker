<?php
/**
 * Created by PhpStorm.
 * User: crell
 * Date: 1/14/15
 * Time: 4:34 PM
 */

namespace Crell\Stacker;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CallableHttpKernel implements HttpMiddlewareInterface
{
    /**
     * @var callable
     */
    protected $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function handle(ServerRequestInterface $request)
    {
        $call = $this->callable;
        $response =  $call($request);

        if (!$response instanceof ResponseInterface) {
            throw new \UnexpectedValueException('Kernel function did not return an object of type Response');
        }

        return $response;
    }
}