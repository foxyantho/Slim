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
use RuntimeException;

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

    const FOUND = 0;
    const NOT_FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;


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

        $methods = array_map('strtoupper', $methods); // RFC 7231, methods are in uppercase


        // create route

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
     * @link   https://github.com/nikic/FastRoute/
     */
    public function dispatch( $httpMethod, $uri) // todo refactor
    {
        foreach( $this->routes as $identifier => $route )
        {
            // get regex of route pattern    /user/{name}/{id:[0-9]+}/{page:int}

            $regex = preg_replace_callback(

                '#' .
                    '\{' .
                        '\s*([a-zA-Z][a-zA-Z0-9_]*)\s*' .

                        '(?:' .
                            ':\s*([^{}]*(?:\{(?-1)\}[^{}]*)*)' .
                        ')?' .
                    '\}' .
                '#',

                [$this, 'matchesCallback'],

                $route->getPattern()
            );

            //    /user/(?P<name>[^/]+)/(?P<id>[0-9]+)


            // check if pattern regex match the uri

            if( preg_match('#^' . $regex . '$#', $uri, $params) )
            {

                // compare server request method with route's allowed http methods

                if( !in_array($httpMethod, $allowedMethods = $route->getMethods()) )
                {
                    return [ static::METHOD_NOT_ALLOWED, $allowedMethods ];
                    // todo security disable this ?
                }

                // only keep named params

                foreach( $params as $key => $value )
                {
                    if( is_int($key) )
                    {
                        unset($params[$key]); // todo
                    }
                }

                // route is found :
                
                return [ static::FOUND, $route, $params];
            }
        }

        // check all routes, but not found

        return [ static::NOT_FOUND ];
    }

    /**
     * Convert a URL parameter into a regular expression
     * 
     * @param  array $m regex matches
     * @return string
     */
    protected function matchesCallback( array $m )
    {
        $condition = '[^/]+'; // default, everything

        if( isset($m[2]) )
        {
            $condition = $m[2]; // if regex: {id: "[0-9]+" }
        }

        return sprintf('(?P<%s>%s)', $m[1], $condition); 
    }

    /**
     * Build URL for named route
     *
     * @param  string $name
     * @param  array  $routeParams        URI segments replacement data
     * @param  array  $queryParams Optional query string parameters
     *
     * @return string
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function urlFor( $identifier, array $routeParams = [], array $queryParams = [] )
    {
        // search if route exist :

        $route = $this->lookup($identifier);

        if( !isset($route) )
        {
            throw new RuntimeException('Named route does not exist for name : ' . $name);
        }

        $pattern = $route->getPattern();


        // alternative without need of explode : /{([a-zA-Z0-9_]+)(?::\s*[^{}]*(?:\{(?-1)\}[^{}]*)*)?}/

        $url = preg_replace_callback('~{([^}]+)}~', function( $match ) use ( $routeParams ) {
            
            $segmentName = explode(':', $match[1])[0];

            if( !isset($routeParams[$segmentName]) )
            {
                throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
            }

            return $routeParams[$segmentName];

        }, $pattern);

        // query params "?page=welcome"

        if( $queryParams )
        {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
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
