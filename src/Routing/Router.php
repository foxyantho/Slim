<?php

/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     MIT
 * @version     2.6.3
 * @package     Slim
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
     * Named pattern index of routes
     * @var null|array
     */
    protected $namedPatternIndex;

    /**
     * default conditions applied to all route instances
     * @var array
     */
    protected static $defaultConditions = [];


    /**
     * Set default route conditions for all instances
     * 
     * @param  array $defaultConditions
     */
    public static function defaultConditions( array $defaultConditions )
    {
        self::$defaultConditions = $defaultConditions;
    }
    /**
     * Get default route conditions for all instances
     * 
     * @return array
     */
    public static function getDefaultConditions()
    {
        return self::$defaultConditions;
    }

    /**
     * Add route
     *
     * @param  string[]        $methods
     * @param  string          $pattern
     * @param  callable|string $handler
     * @return \Slim\Interfaces\RouteInterface
     * @throws InvalidArgumentException if the route pattern isn't a string
     */
    public function map( array $methods, $pattern, $handler )
    {
        if( !is_string($pattern) )
        {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // Add route
        $route = new Route($methods, $pattern, $handler);

        $this->routes[] = $route;


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
    public function dispatch( $httpMethod, $uri)
    {

        foreach( $this->routes as $route )
        {
            // get regex of route pattern    /user/{name}/{id:[0-9]+}

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
        if( isset($m[2]) ) // {id:"[0-9]+"}
        {
            return sprintf( '(?P<%s>%s)', $m[1], $m[2] );
        }

        if( isset(static::$defaultConditions[$m[1]]) ) // {"id"}
        {
            return sprintf( '(?P<%s>%s)', $m[1], static::$defaultConditions[$m[1]] );
        }

        return sprintf( '(?P<%s>%s)', $m[1], '[^/]+' ); // default, everything
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

        if( is_null($this->namedPatternIndex) )
        {
            // no named routes, so lazy-build it

            $this->buildNamedPatternIndex();
        }

        if( !isset($this->namedPatternIndex[$name]) )
        {
            throw new RuntimeException('Named route does not exist for name : ' . $name);
        }


        $pattern = $this->namedPatternIndex[$name];


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

        // query params ?x=x

        if( $queryParams )
        {
            $url .= '?' . http_build_query($queryParams);
        }


        return $url;
    }

    /**
     * Build index of pattern for named routes ; used in urlFor()
     */
    protected function buildNamedPatternIndex()
    {
        $this->namedRoutes = [];

        foreach( $this->routes as $route )
        {
            if( $name = $route->getName() )
            {
                $this->namedPatternIndex[$name] = $route->getPattern();
            }
        }
    }



}
