<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

namespace Slim\Routing;

use Slim\Routing\Interfaces\RouterInterface;
use Slim\Http\Interfaces\RequestInterface;

use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\RouteParser\Std as StdParser;
use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedGenerator;
use FastRoute\Dispatcher\GroupCountBased as RouteDispatcherGroupCountBased;

use InvalidArgumentException;
use RuntimeException;

/**
 * Router
 *
 * This class organizes Slim application route objects. It is responsible
 * for registering route objects, assigning names to route objects,
 * finding routes that match the current HTTP request, and creating
 * URLs for a named route.
 */
class Router extends RouteCollector implements RouterInterface
{

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Named routes
     *
     * @var null|Route[]
     */
    protected $namedRoutes;


    /**
     * Create new router
     *
     * @param \FastRoute\RouteParser   $parser
     * @param \FastRoute\DataGenerator $generator
     */
    public function __construct( RouteParser $parser = null, DataGenerator $generator = null )
    {
        $parser = $parser ?: new StdParser;

        $generator = $generator ?: new GroupCountBasedGenerator;

        parent::__construct($parser, $generator);
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $handler The route callable
     * @return \Slim\Interfaces\RouteInterface
     * @throws InvalidArgumentException if the route pattern isn't a string
     */
    public function map( $methods, $pattern, $handler )
    {
        if( !is_string($pattern) )
        {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // Add route
        $route = new Route($methods, $pattern, $handler);

        // FastRoute
        $this->addRoute($methods, $pattern, [$route, 'run']);


        $this->routes[] = $route;

        return $route;
    }

    /**
     * Dispatch router for HTTP request
     *
     * @param  RequestInterface $request The current HTTP request object
     *
     * @return array
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch( RequestInterface $request )
    {
        $dispatcher = new GroupCountBasedDispatcher($this->getData());

        return $dispatcher->dispatch(
            $request->getMethod(),
            $request->getUriPath()
        );
    }

    /**
     * Build URL for named route
     *
     * @param  string $name        Route name
     * @param  array  $data        Route URI segments replacement data
     * @param  array  $queryParams Optional query string parameters
     *
     * @return string
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function urlFor( $name, array $data = [], array $queryParams = [] )
    {
        if( is_null($this->namedRoutes) )
        {
            // no named routes, so build it
            $this->buildNameIndex();
        }

        if( !isset($this->namedRoutes[$name]) )
        {
            throw new RuntimeException('Named route does not exist for name: ' . $name);
        }


        $route = $this->namedRoutes[$name];

        $pattern = $route->getPattern();

        // url/x/y

        $url = preg_replace_callback('/{([^}]+)}/', function( $match ) use ( $data )
        {
            $segmentName = explode(':', $match[1])[0];

            if( !isset($data[$segmentName]) )
            {
                throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
            }

            return $data[$segmentName];

        }, $pattern);

        // query params ?x=x

        if( $queryParams )
        {
            $url .= '?' . http_build_query($queryParams);
        }


        return $url;
    }

    /**
     * Build index of named routes
     */
    protected function buildNameIndex()
    {
        $this->namedRoutes = [];

        foreach( $this->routes as $route )
        {
            if( $name = $route->getName() )
            {
                $this->namedRoutes[$name] = $route;
            }
        }
    }


}
