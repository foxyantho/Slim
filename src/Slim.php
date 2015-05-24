<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

namespace Slim;

use Slim\Http\Interfaces\RequestInterface;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Http\Environment as HttpEnvironment;
use Slim\Http\Headers as HttpHeaders;
use Slim\Http\Request as HttpRequest;
use Slim\Http\Response as HttpResponse;

use Slim\Routing\Router;
use FastRoute\Dispatcher as RouteDispatcher;

use Closure;

use Slim\Exception as SlimException;
use Exception;

/**
 * App
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Slim Framework application. This
 * is also a \Pimple\Container instance, meaning you can
 * register custom Pimple service providers on each
 * \Slim\App instance. The \Slim\App class also accepts
 * Slim Framework middleware.
 *
 * @property-read array $settings App settings
 * @property-read \Slim\Interfaces\Http\EnvironmentInterface $environment 
 * @property-read \Psr\Http\Message\RequestInterface $request
 * @property-read \Psr\Http\Message\ResponseInterface $response
 * @property-read \Slim\Interfaces\RouterInterface $router
 * @property-read callable $errorHandler
 * @property-read callable function($request, $response) $notFoundHandler
 * @property-read callable function($request, $response, $allowedHttpMethods) $notAllowedHandler
 */
class Slim
{
    use ResolveCallable;
    use MiddlewareAware;

    /**
     * Current version
     *
     * @var string
     */
    const VERSION = '3.0.0';


    protected $settings;

    protected $environment;

    protected $request;

    protected $response;

    protected $router;


    public function __construct( array $userSettings = [] )
    {
        // settings

        $this->settings = array_merge(static::getDefaultSettings(), $userSettings);

        // environment

        $this->environment = new HttpEnvironment($_SERVER);

        // request

        $method = $this->environment['REQUEST_METHOD'];

        $request_headers = HttpHeaders::createFromEnvironment($this->environment);

        $body = file_get_contents('php://input'); // stream_get_contents(fopen('php://input', 'r'));

        $this->request = new HttpRequest($method, $request_headers, $body);

        // response
        
        $protocolVersion = $this->settings['httpVersion'];

        $response_headers = new HttpHeaders(['Content-Type' => 'text/html']);

        $this->response = ( new HttpResponse(200, $response_headers) )->protocolVersion($protocolVersion);

        // router
        
        $this->router = new Router;
    }

    /**
     * Get default settings
     * 
     * @return array
     */
    public static function getDefaultSettings()
    {
        return [
            'httpVersion' => '1.1'
        ];
    }


    /********************************************************************************
     * Router proxy methods
     *******************************************************************************/


    /**
     * Add GET route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function get( $pattern, $callable )
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * Add POST route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function post( $pattern, $callable )
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * Add PUT route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function put( $pattern, $callable )
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * Add PATCH route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function patch( $pattern, $callable )
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * Add DELETE route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function delete( $pattern, $callable )
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * Add OPTIONS route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function options( $pattern, $callable )
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route with multiple methods
     *
     * @param  string[] $methods  Numeric array of HTTP method names
     * @param  string   $pattern  The route URI pattern
     * @param  mixed    $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function map( array $methods, $pattern, $callable )
    {
        $callable = is_string($callable) ? $this->resolveCallable($callable) : $callable;

        if( $callable instanceof Closure )
        {
            $callable = $callable->bindTo($this);
        }

        $route = $this->router->map($methods, $pattern, $callable);

        return $route;
    }

    /**
     * Route Groups
     *
     * This method accepts a route pattern and a callback. All route
     * declarations in the callback will be prepended by the group(s)
     * that it is in.
     *
     * Accepts the same parameters as a standard route so:
     * (pattern, middleware1, middleware2, ..., $callback)
     */
    public function group()
    {
        $args = func_get_args();
        $pattern = array_shift($args);
        $callable = array_pop($args);
        $this->container->get('router')->pushGroup($pattern, $args);
        if (is_callable($callable)) {
            call_user_func($callable);
        }
        $this->container->get('router')->popGroup();
    }

    /********************************************************************************
     * Application flow methods
     *******************************************************************************/

    /**
     * Stop
     *
     * This method stops the application and sends the provided
     * Response object to the HTTP client.
     *
     * @param  ResponseInterface $response
     *
     * @throws \Slim\Exception
     */
    public function stop( ResponseInterface $response )
    {
        throw new SlimException($response);
    }

    /**
     * Halt
     *
     * This method prepares a new HTTP response with a specific
     * status and message. The method immediately halts the
     * application and returns a new response with a specific
     * status and message.
     *
     * @param  int    $status  The desired HTTP status
     * @param  string $message The desired HTTP message
     *
     * @throws \Slim\Exception
     */
    public function halt( $status, $message = '' )
    {
        $response = $this->response->status($status);

        $response->body($message);

        $this->stop($response);
    }

    /********************************************************************************
     * Runner
     *******************************************************************************/

    /**
     * Run application
     *
     * This method traverses the application middleware stack,
     * and it returns the resultant Response object to the HTTP client.
     */
    public function run()
    {
        $request = $this->request;
        $response = $this->response;

        // Traverse middleware stack

        try
        {
            $response = $this->callMiddlewareStack($request, $response);
        }
        catch ( SlimException $e )
        {
            $response = $e->getResponse();
        }
        catch( Exception $e )
        {
            $errorHandler = 'errorHandler'; // @FIXME

            $response = $errorHandler($request, $response, $e);
        }

        // Finalize response : fetch status, header, and body

        list($status, $headers, $body) = $response->finalize();

        // Send response

        if( !headers_sent() )
        {
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            // Headers
            foreach( $headers as $name => $values )
            {
                foreach( $values as $value )
                {
                    header(sprintf('%s: %s', $name, $value), false); // multiples
                }
            }
        }

        // Body

        if( $body )
        {
            echo $body;
        }

        // response, if needed

        return $response;
    }

    /**
     * Invoke application
     *
     * This method implements the middleware interface. It receives
     * Request and Response objects, and it returns a Response object
     * after dispatching the Request object to the appropriate Route
     * callback routine.
     *
     * @param  RequestInterface  $request  The most recent Request object
     * @param  ResponseInterface $response The most recent Response object
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        $routeInfo = $this->container->get('router')->dispatch($request);
        if ($routeInfo[0] === \FastRoute\Dispatcher::FOUND) {
            // URL decode the named arguments from the router
            $attributes = $routeInfo[2];
            array_walk($attributes, function (&$v, $k) {
                $v = urldecode($v);
            });
            return $routeInfo[1]($request->withAttributes($attributes), $response);
        }
        if ($routeInfo[0] === \FastRoute\Dispatcher::NOT_FOUND) {
            $notFoundHandler = $this->container->get('notFoundHandler');
            return $notFoundHandler($request, $response);
        }
        if ($routeInfo[0] === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            $notAllowedHandler = $this->container->get('notAllowedHandler');
            return $notAllowedHandler($request, $response, $routeInfo[1]);
        }
    }

    /**
     * Perform a sub-request from within an application route
     *
     * This method allows you to prepare and initiate a sub-request, run within
     * the context of the current request. This WILL NOT issue a remote HTTP
     * request. Instead, it will route the provided URL, method, headers,
     * cookies, body, and server variables against the set of registered
     * application routes. The result response object is returned.
     *
     * @param  string            $method      The request method (e.g., GET, POST, PUT, etc.)
     * @param  string            $path        The request URI path
     * @param  array             $headers     The request headers (key-value array)
     * @param  array             $cookies     The request cookies (key-value array)
     * @param  string            $bodyContent The request body
     * @return ResponseInterface
     */
    public function subRequest($method, $path, array $headers = [], array $cookies = [], $bodyContent = '')
    {
        $env = $this->container->get('environment');
        $uri = Http\Uri::createFromEnvironment($env)->withPath($path);
        $headers = new Http\Headers($headers);
        $serverParams = new Collection($env->all());
        $body = new Http\Body(fopen('php://temp', 'r+'));
        $body->write($bodyContent);
        $body->rewind();
        $request = new Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
        $response = $this->container->get('response');

        return $this($request, $response);
    }
}
