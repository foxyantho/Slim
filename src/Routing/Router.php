<?php

/**
 * Slim - a micro PHP 5 framework
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing;

use Slim\Routing\Interfaces\RouterInterface;
use Slim\Routing\Interfaces\RouteInterface;

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
     * look-up table pattern of routes ; used in urlfor()
     * @var null|array
     */
    protected $lookupTable;

    /**
     * URI base path "//example.com/folder/" used in urlfor()
     * @var string
     */
    protected $uriRoot;


    /**
     * Set the URI base path used in urlfor()
     *
     * @param string $uri
     *
     * @return self
     */
    public function setUriRoot( $uri )
    {
        if( !is_string($uri) )
        {
            throw new InvalidArgumentException('Router basePath must be a string');
        }

        $this->uriRoot = $uri;

        return $this;
    }

    /**
     * Add route
     *
     * @param  string[]        $methods
     * @param  string          $pattern
     * @param  callable|string $handler
     * 
     * @return \Slim\Interfaces\RouteInterface
     * @throws InvalidArgumentException if the route pattern isn't a string
     */
    public function map( array $methods, $pattern, $handler )
    {
        if( !is_string($pattern) )
        {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // According to RFC methods are defined in uppercase (See RFC 7231)
        $methods = array_map('strtoupper', $methods);

        // Add route

        $route = $this->newRoute($methods, $pattern, $handler);

        $this->routes[] = $route;


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
    protected function newRoute( array $methods, $pattern, $handler )
    {
        return ( new Route($methods, $pattern, $handler) );
    }

    /**
     * Dispatch router for HTTP request
     * 
     * @param  string $httpMethod
     * @param  string $uri
     * @return array
     * @link   https://github.com/nikic/FastRoute/
     */
    public function dispatch( $httpMethod, $uri)
    {

        foreach( $this->routes as $route )
        {
            // get regex of route pattern    /user/{name}/{id:[0-9]+}/{page:int}

            $regex = preg_replace_callback(

                '#' .
                    '{' .
                        '\s*([a-zA-Z][a-zA-Z0-9_]*)\s*' .

                        '(?:' .
                            ':\s*([^{}]*(?:{(?-1)}[^{}]*)*)' .
                        ')?' .
                    '}' .
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
                }

                // only keep named params

                foreach( $params as $key => $value )
                {
                    if( is_int($key) )
                    {
                        unset($params[$key]);
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
     * @param  array  $data        URI segments replacement data
     * @param  array  $queryParams Optional query string parameters
     *
     * @return string
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function urlFor( $name, array $data = [], array $queryParams = [] )
    {

        if( is_null($this->lookupTable) )
        {
            // no named routes, so lazy-build it

            $this->buildLookupTable();
        }

        if( !isset($this->lookupTable[$name]) )
        {
            throw new RuntimeException('Named route does not exist for name : ' . $name);
        }


        $pattern = $this->lookupTable[$name];


        // alternative without need of explode : /{([a-zA-Z0-9_]+)(?::\s*[^{}]*(?:\{(?-1)\}[^{}]*)*)?}/

        $url = preg_replace_callback(

            '#{([^}]+)}#',

            function( $match ) use ( $data )
            {
                $segmentName = explode(':', $match[1])[0];

                if( !isset($data[$segmentName]) )
                {
                    throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
                }

                return $data[$segmentName];
            },

            $pattern
        );

        // set uri base path if set

        if( $this->uriRoot )
        {
            $url = $this->uriRoot . ltrim($url, '/');
        }

        // query params ?x=x

        if( $queryParams )
        {
            $url .= '?' . http_build_query($queryParams);
        }


        return $url;
    }

    /**
     * lazy-load index of pattern for named routes ; used in urlFor()
     */
    protected function buildLookupTable()
    {
        $this->lookupTable = [];

        foreach( $this->routes as $route )
        {
            if( $name = $route->getName() )
            {
                $this->lookupTable[$name] = $route->getPattern();
            }
        }
    }



}
