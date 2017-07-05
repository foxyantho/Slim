<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use Slim\Routing\Interfaces\RouteInvocationStrategyInterface;


class RouteInvocationStrategy implements RouteInvocationStrategyInterface
{
    /**
     * Invoke a route callable with request, response, and all route parameters as an array of arguments. ( old FoundHandler )
     *
     * @param callable $callable
     * @param Request  $request
     * @param Response $response
     * @param array    $routeArguments
     * @return ResponseInterface
     */
    public function __invoke( Request $request, Response $response, callable $callable, array $routeArguments )
    {

        $newResponse = call_user_func_array($callable, array_merge([$request, $response], $routeArguments));

        // if new response is a string, then append it to the originale response

        if( is_string($newResponse) )
        {
            $newResponse = $response->write($newResponse);
        }

        return $newResponse;
    }


}
