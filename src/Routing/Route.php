<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

namespace Slim\Routing;

use Slim\Routing\Interfaces\RouteInterface;
use Slim\ResolveCallableTrait;
use Slim\MiddlewareAwareTrait;

use Slim\Http\Interfaces\RequestInterface;
use Slim\Http\Interfaces\ResponseInterface;

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
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * Route pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Route callable
     *
     * @var callable
     */
    protected $callable;

    /**
     * Route name
     *
     * @var null|string
     */
    protected $name;


    /**
     * Create new route
     *
     * @param string[] $methods       The route HTTP methods
     * @param string   $pattern       The route pattern
     * @param callable $callable      The route callable
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
     * Get route callable
     *
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set route name
     *
     * @param string $name
     */
    public function setName( $name )
    {
        if( !is_string($name) )
        {
            throw new InvalidArgumentException('Route name must be a string');
        }

        $this->name = $name;

        return $this;
    }


    /********************************************************************************
    * Route Runner
    *******************************************************************************/


    /**
     * Run route : traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     */
    public function run( RequestInterface $request, ResponseInterface $response )
    {
        // Traverse middleware stack and fetch updated response
        return $this->callMiddlewareStack($request, $response);
    }

    /**
     * Dispatch route callable against current Request and Response objects
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param RequestInterface       $request  The current Request object
     * @param ResponseInterface      $response The current Response object
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function __invoke( RequestInterface $request, ResponseInterface $response )
    {
        // invoke route callable
        try
        {
            $callable = $this->callable;

            ob_start();

            $newReponse = $callable($request, $response, $request->getAttributes());
            //$newResponse = call_user_func_array($callable, [$request, $response] + $this->parsedArgs);
            // @TODO @FIXME use ?
            $output = ob_get_clean();

        }
        catch( Exception $e )
        {
            ob_end_clean();

            throw $e;
        }
        
        // if route callback returns a ResponseInterface, then use it
        if( $newReponse instanceof ResponseInterface )
        {
            $response = $newReponse;
        }

        // if route callback retuns a string, then append it to the response
        if( is_string($newReponse) )
        {
            $response->body($newReponse);
        }
        
        // append output buffer content if there is any
        if( $output )
        {
            $response->body($output);
        }


        return $response;
    }


}
