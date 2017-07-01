<?php

namespace Slim\Routing\Interfaces;

use Slim\Http\Interfaces\RequestInterface;
use Slim\Http\Interfaces\ResponseInterface;


interface RouteInvocationStrategyInterface
{

    public function __invoke( RequestInterface $request, ResponseInterface $response, callable $callable, array $routeArguments );


}
