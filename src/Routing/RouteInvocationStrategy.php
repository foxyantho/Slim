<?php

namespace Slim\Routing;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use Slim\Routing\Interfaces\RouteInvocationStrategyInterface;


class RouteInvocationStrategy implements RouteInvocationStrategyInterface
{
    /**
     * Invoke a route callable with request, response, and all route parameters as an array of arguments.
     *
     * @param callable $callable
     * @param Request  $request
     * @param Response $response
     * @param array    $routeArguments
     * @return ResponseInterface
     */
    public function __invoke( Request $request, Response $response, callable $callable, array $routeArguments )
    {

        $output = call_user_func_array($callable, [ $request, $response ] + $routeArguments);


        return $output;
    }


}
