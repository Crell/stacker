<?php
/**
 * Created by PhpStorm.
 * User: crell
 * Date: 1/14/15
 * Time: 4:12 PM
 */

namespace Crell\Stacker;


use Psr\Http\Message\ServerRequestInterface;

interface HttpMiddlewareInterface
{

    public function handle(ServerRequestInterface $request);

}