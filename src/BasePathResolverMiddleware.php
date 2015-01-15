<?php
/**
 * Created by PhpStorm.
 * User: crell
 * Date: 1/14/15
 * Time: 5:31 PM
 */

namespace Crell\Stacker;

use Phly\Http\Uri;
use Psr\Http\Message\ServerRequestInterface;

class BasePathResolverMiddleware implements HttpMiddlewareInterface
{

    /**
     * @var HttpMiddlewareInterface
     */
    protected $inner;

    /**
     * @var string
     */
    protected $basePath;

    public function __construct(HttpMiddlewareInterface $inner, $basePath)
    {
        $this->inner = $inner;
        $this->basePath = $basePath;
    }

    public function handle(ServerRequestInterface $request)
    {
        // No, really, this step is total nonsense.
        $parts = parse_url($request->getUrl());
        $path = $parts['path'];

        if (strpos($path, $this->basePath) == 0) {
            $newPath = substr($path, strlen($this->basePath));
            $request = $request->setUrl($newPath);
        }

        return $this->inner->handle($request);
    }

}