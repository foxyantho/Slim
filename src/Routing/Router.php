<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing;

use Slim\Routing\Interfaces\RouterInterface;
use Slim\Routing\Interfaces\RouteInvocationStrategyInterface;

use Slim\Http\Interfaces\RequestInterface as Request;

use InvalidArgumentException;
use Slim\Routing\Exceptions\NotFoundException;
use Slim\Routing\Exceptions\MethodNotAllowedException;

/**
 * Router
 *
 * This class organizes, iterates, and dispatches \Slim\Route objects.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class Router implements RouterInterface
{

    /**
     * lookup of all route objects
     * @var array
     */
    protected $routes = [];


    /**
     * Add route
     *
     * @param  string          $identifier
     * @param  string[]        $methods
     * @param  string          $pattern
     * @param  callable|string $handler
     * 
     * @return \Slim\Interfaces\RouteInterface
     * @throws InvalidArgumentException if the route pattern isn't a string
     */
    public function map( $identifier, array $methods, $pattern, $handler )
    {
        if( !is_string($pattern) )
        {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // create route

        $methods = array_map('strtoupper', $methods); // RFC 7231, methods are in uppercase

        $route = $this->createRoute($methods, $pattern, $handler);

        // add route the the list

        $this->routes[$identifier] = $route;


        return $route;
    }

    /**
     * Create a new Route object.
     *
     * @param  array  $methods
     * @param  string $pattern
     * @param  mixed  $handler
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    protected function createRoute( array $methods, $pattern, $handler )
    {
        $route = new Route($methods, $pattern, $handler);

        return $route;
    }

    /**
     * Dispatch router for HTTP request
     * 
     * @param  string $httpMethod
     * @param  string $uri
     * @return array
     * 
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function dispatch( $httpMethod, $uri )
    {

        foreach( $this->routes as $identifier => $route )
        {
            // check if pattern regex match the uri

            $regex = '#^' . str_replace('#', '\#', $route->getPattern()) . '$#';

            if( preg_match($regex, $uri, $params) )
            {
                // compare server request method with route's allowed http methods

                // todo : if declare route with methods separatly : map('get',url_1) map('post',url_1)

                $allowedMethods = $route->getMethods();

                if( !in_array($httpMethod, $allowedMethods) )
                {
                    throw new MethodNotAllowedException($allowedMethods);
                }

                array_shift($params); // remove capture

                // route is found :
                
                return [$route, $params];
            }
        }

        // check all routes, but not found

        throw new NotFoundException;
    }

    /**
     * Search for a route by it's name
     *
     * @param string $identifier
     * @return Route|void
     */
    protected function lookup( $identifier )
    {
        if( isset($this->routes[$identifier]) )
        {
            return $this->routes[$identifier];
        }
    }


}
