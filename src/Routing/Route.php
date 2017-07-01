<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing;

use Slim\Routing\Interfaces\RouteInterface;
use Slim\ResolveCallableTrait;
use Slim\MiddlewareAwareTrait;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use Closure;
use Exception;
use InvalidArgumentException;

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
     * Route handler
     * @var mixed
     */
    protected $handler;

    /**
     * Invacation stategy of the callable ; how it should be handled
     * @var todo
     */
    protected $invocationStrategy;


    /**
     * Create new route
     *
     * @param string[]     $methods The route HTTP methods
     * @param string       $pattern The route pattern
     * @param mixed        $handler The route handler
     * @param RouteGroup[] $groups The parent route groups
     */
    public function __construct( $methods, $pattern, $handler )
    {
        $this->methods = $methods;

        $this->pattern = $pattern;

        $this->handler = $handler;
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
    public function getHandler()
    {
        return $this->handler;
    }


    /********************************************************************************
     * Route Runner
     *******************************************************************************/

    /**
     * Get route invocation strategy
     *
     * @return todo
     */
    public function getInvocationStrategy()
    {
        return $this->invocationStrategy;
    }

    /**
     * Set route invocation strategy
     *
     * @param todo $strategy
     */
    public function setInvocationStrategy(InvocationStrategyInterface $strategy)
    {
        $this->invocationStrategy = $strategy;
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
    public function run( Request $request, Response $response )
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
    public function __invoke( Request $request, Response $response )
    {

        return [ $request, $response, $this->handler ];

        //@TODO: @FIXME:  fix this mess & foundhandler
    }


}
