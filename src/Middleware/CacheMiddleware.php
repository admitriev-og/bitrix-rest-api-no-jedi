<?php

namespace BitrixRestApi\Middleware;

use BitrixRestApiCache\Cache\PhpCache;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Middleware\RoutingMiddleware;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class CacheMiddleware
{
    /** @var Request $request */
    public function __invoke($request, $handler)
    {
        $fromCache = false;

        if ($request->getMethod() === 'GET') {
            $cache = new PhpCache($request);
            $result = $cache->init();
            if (!$result) {
                /** @var Response $response */
                $response = $handler->handle($request);
                $body = json_decode($response->getBody(), true);
                $cache->cache($body);
            } else {
                $response = new Response();
                $body = $response->getBody();
                $body->write(json_encode($result, JSON_HEX_QUOT | JSON_HEX_TAG));
                $response->withBody($body);
                $fromCache = true;
            }
        }

        return $response->withHeader('X-Cache-Status', $fromCache ? 'hit' : 'miss');
    }
}