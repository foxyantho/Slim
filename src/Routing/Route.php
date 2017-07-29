<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing;

use Slim\Routing\Interfaces\RouteInterface;
use Slim\ResolveCallableTrait;
use Slim\MiddlewareAwareTrait;

use Slim\Routing\Interfaces\RouteInvocationStrategyInterface as InvocationStrategyInterface;

use Slim\Http\Interfaces\RequestInterface;
use Slim\Http\Interfaces\ResponseInterface;

use Closure;
use Exception;
use UnexpectedValueException;
use RuntimeException;

/**
 * Route
 */
class Route implements RouteInterface
{

    use ResolveCallableTrait;

    use MiddlewareAwareTrait {
        add as addMiddleware;
    }


    /**
     * HTTP methods supported by this route
     * @var string[]
     */
    protected $methods = [];

    /**
     * Route pattern "/hello/world"
     * @var string
     */
    protected $pattern;

    /**
     * Route callable function
     * @var mixed
     */
    protected $callable;

    /**
     * Route parameters
     * @var array
     */
    protected $arguments = [];

    /**
     * $handler invocation strategy
     * @var InvocationStrategyInterface
     */
    protected $invocationStrategy;


    /**
     * Create new route
     *
     * @param string[]     $methods The route HTTP methods
     * @param string       $pattern The route pattern
     * @param mixed        $callable The route callable function
     * @param RouteGroup[] $groups The parent route groups
     */
    public function __construct( $methods, $pattern, $callable )
    {
        $this->methods = $methods;

        $this->pattern = $pattern;

        $this->callable = $callable;
    }

    /**
     * Add middleware : prepends new middleware to the route's middleware stack.
     *
     * @param  mixed    $callable The callback routine
     * @return RouteInterface
     */
    public function add( $callable )
    {
        $callable = $this->resolveCallable($callable);

        return $this->addMiddleware($callable);
    }

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Get route handler
     *
     * @return mixed
     */
    public function getCallable()
    {
        return $this->handler;
    }

    /**
     * Retrieve route arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Get default route invocation strategy
     *
     * @return RouteInvocationStrategyInterface|null
     */
    public function getInvocationStrategy()
    {
        return $this->invocationStrategy;
    }


    /********************************************************************************
     * Route Runner
     *******************************************************************************/

    /**
     * Prepare the route just before run() : add the arguments & strategy
     *
     * @param ServerRequestInterface $request
     * @param array $arguments
     */
    public function prepare( InvocationStrategyInterface $handler, array $arguments = [] )
    {
        $this->invocationStrategy = $handler;

        $this->arguments = $arguments;
    }


    /**
     * Run route : traverses the middleware stack, including the route's handler
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function run( RequestInterface $request, ResponseInterface $response )
    {
        // Traverse middleware stack and fetch updated response

        return $this->callMiddlewareStack($request, $response);
    }

    /**
     * Dispatch route handler against current Request and Response objects
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param RequestInterface       $request  The current Request object
     * @param ResponseInterface      $response The current Response object
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception  if the route handler throws an exception
     */
    public function __invoke( RequestInterface $request, ResponseInterface $response )
    {
        // Resolve route callable

        $callable = $this->resolveCallable($this->callable);

        // call the route handler

        $handler = $this->getInvocationStrategy();

        if( !$handler )
        {
            throw new RuntimeException('Route invocation strategy is missing');
        }

        // call the route handler

        $routeResponse = $handler($request, $response, $callable, $this->arguments);

        if( !$routeResponse instanceof ResponseInterface )
        {
            throw new UnexpectedValueException('Route handler must return an instance of ResponseInterface');
        }

        return $routeResponse;
    }


}
