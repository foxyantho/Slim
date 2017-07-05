<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use Slim\Http\Environment as HttpEnvironment;
use Slim\Http\Headers as HttpHeaders;
use Slim\Http\Request as HttpRequest;
use Slim\Http\Response as HttpResponse;

use Slim\Routing\Router;
use Slim\Routing\RouteInvocationStrategy;
use Slim\Routing\Interfaces\RouteInvocationStrategyInterface;

use Slim\Handlers\NotFound as NotFoundHandler;
use Slim\Handlers\NotAllowed as NotAllowedHandler;
use Slim\Handlers\Exception as ExceptionHandler;

use Closure;

use Exception;
//use Throwable;
use Slim\Exceptions\SlimException;
use Slim\Routing\Exceptions\NotFoundException;
use Slim\Routing\Exceptions\MethodNotAllowedException;

/**
 * App
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Slim Framework application.
 * The \Slim\App class also accepts Slim Framework middleware.
 */
class Slim
{

    use ResolveCallableTrait;

    use MiddlewareAwareTrait {
        add as addMiddleware;
    }

    /**
     * Current version
     * @var string
     */
    const VERSION = '3.0.0';


    // Setting, server env

    protected $settings;

    protected $environment;

    // Container ; if needed

    protected $container = [];

    // Request ; Response flow

    protected $request;

    protected $response;

    // Router

    protected $router;

    protected $routeInvocationStrategy; // AKA foundHandler

    // Route handlers

    protected $foundHandler;

    protected $notFoundHandler;

    protected $notAllowedHandler;

    protected $exceptionHandler;

    // App instance

    protected static $instance;



    /**
     * Create new application
     */
    public function __construct( array $userSettings = [] )
    {
        // settings

        $this->settings = array_merge(static::getDefaultSettings(), $userSettings);

        // environment

        $this->environment = new HttpEnvironment($_SERVER);

        // request

        $method = $this->environment['request.method'];

        $environmentHeaders = $this->environment->getAllHeaders(); // getallheaders

        $requestHeaders = new HttpHeaders($environmentHeaders); 

        $body = file_get_contents('php://input'); // stream_get_contents(fopen('php://input', 'r'));

        $this->request = new HttpRequest($method, $requestHeaders, $this->environment, $body);

        // response

        $protocolVersion = $this->settings['httpVersion'];

        $responseHeaders = new HttpHeaders(['content.type' => 'text/html']);

        $this->response = new HttpResponse(200, $responseHeaders);
    
        $this->response->protocolVersion($protocolVersion);

        // router

        $this->router = new Router;


        // instance, if needed

        static::$instance = $this;
    }

    /**
     * Get default settings
     * 
     * @return array
     */
    public static function getDefaultSettings()
    {
        return [

            'httpVersion' => '1.1',

            'displayErrorDetails' => true,


            // Templates
    
            'template' => [],

            // Database

            'database' => []
        ];
    }

    /**
     * Set the globally available instance of the app ; Needed to be instantiated first
     *
     * @return static|null
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Container's get
     * 
     * @param  mixed $key
     * @return mixed|null
     */
    public function __get( $key )
    {
        if( isset($this->container[$key]) )
        {
            return $this->container[$key];
        }

        return null; // default
    }

    /**
     * Container's set
     * 
     * @param mixed $key
     * @param mixed $value
     */
    public function __set( $key, $value )
    {
        $this->container[$key] = $value;
    }



    /********************************************************************************
     * Settings management
     *******************************************************************************/


    /**
     * Does app have a setting with given key?
     *
     * @param string $key
     * @return bool
     */
    public function hasSetting( $key )
    {
        return isset($this->settings[$key]);
    }

    /**
     * Get app settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Get app setting with given key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting( $key, $default = null )
    {
        return $this->hasSetting($key) ? $this->settings[$key] : $default;
    }

    /**
     * Merge a key-value array with existing app settings
     *
     * @param array $settings
     */
    public function addSettings( array $settings )
    {
        $this->settings = array_merge($this->settings, $settings);
    }

    /**
     * Add single app setting
     *
     * @param string $key
     * @param mixed $value
     */
    public function addSetting( $key, $value )
    {
        $this->settings[$key] = $value;
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
    public function get( $routeName, $pattern, $callable )
    {
        return $this->map($routeName, ['GET'], $pattern, $callable);
    }

    /**
     * Add POST route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function post( $routeName, $pattern, $callable )
    {
        return $this->map($routeName, ['POST'], $pattern, $callable);
    }

    /**
     * Add PUT route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function put( $routeName, $pattern, $callable )
    {
        return $this->map($routeName, ['PUT'], $pattern, $callable);
    }

    /**
     * Add PATCH route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function patch( $routeName, $pattern, $callable )
    {
        return $this->map($routeName, ['PATCH'], $pattern, $callable);
    }

    /**
     * Add DELETE route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function delete( $routeName, $pattern, $callable )
    {
        return $this->map($routeName, ['DELETE'], $pattern, $callable);
    }

    /**
     * Add OPTIONS route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function options( $routeName, $pattern, $callable )
    {
        return $this->map($routeName, ['OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route for any HTTP method
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $callable The route callback routine
     * @return \Slim\Interfaces\RouteInterface
     */
    public function any( $routeName, $pattern, $callable )
    {
        return $this->map($routeName, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route with multiple methods
     *
     * @param  string[] $methods  Numeric array of HTTP method names
     * @param  string   $pattern  The route URI pattern
     * @param  mixed    $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function map( $routeName, array $methods, $pattern, $callable )
    {
        // todo callable->bindTo

        $route = $this->router->map($routeName, $methods, $pattern, $callable);

        return $route;
    }


    /********************************************************************************
     * Application flow methods
     *******************************************************************************/

    /**
     * Add middleware
     * This method prepends new middleware to the route's middleware stack.
     * 
     * @param  mixed    $callable
     * @return $this
     */
    public function add( $callable )
    {
        $callable = $this->resolveCallable($callable);

        return $this->addMiddleware($callable);
    }


    /********************************************************************************
     * Runner
     *******************************************************************************/


    /**
     * Run application
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     *
     * @param bool|false $silent
     * @return ResponseInterface
     *
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function run( $silent = false )
    {
       // traverse middleware stack ; process the request

        try {
            $response = $this->callMiddlewareStack($this->request, $this->response);
        }
        catch( Exception $e ) {

            // catch any other exception

            $handler = $this->getExceptionHandler();

            $response = $handler($this->request, $this->response, $e);
        }
        // todo thrwable

        // send response

        $response = $this->finalize($response);

        if( !$silent )
        {
            $this->respond($response);
        }

        // get the final response

        return $response;
    }

    /**
     * Finalize response
     *
     * @param  ResponseInterface $response
     * @return ResponseInterface
     */
    protected function finalize( Response $response )
    {
        // stop PHP sending a Content-Type automatically
        ini_set('default_mimetype', '');

        if( $response->isEmpty() )
        {
            return $response->withoutHeader('content.type')
                            ->withoutHeader('content.length');
        }

        // it has body :

        if( ( $size = $response->getBodyLength() ) > 0 )
        {
            $response->header('content.length', (string) $size);
        }


        return $response;
    }

    /**
     * Send the response the client
     *
     * @param ResponseInterface $response
     */
    protected function respond( Response $response )
    {
        // send response

        if( !headers_sent() )
        {
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            // headers

            foreach( $response->getHeaders() as $name => $value )
            {
                $name = str_replace('.', '-', ucwords($name, '.')); // convert header to right format

                header(sprintf('%s: %s', $name, $value), false); // don't replace existing
            }
        }

        // Body

        if( !$response->isEmpty() )
        {
            echo $response->getBody();
        }
    }

    /**
     * Invoke application : implements the middleware interface.
     * It receives Request and Response objects, and it returns a
     * Response object after dispatching the Request object to the
     * appropriate Route callback routine.
     *
     * @param  RequestInterface  $request  The most recent Request object
     * @param  ResponseInterface $response The most recent Response object
     * @return ResponseInterface
     * 
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function __invoke( Request $request, Response $response )
    {
        try {

            $routeInfo = $this->router->dispatch(
                $request->getMethod(),
                $request->getUriPath()
            );

            list($route, $routeArguments) = $routeInfo;

            // URL decode the named arguments from the router

            $routeArguments = array_map('urldecode', $routeArguments);

            // prepare the route

            $handler = $this->getRouteInvocationStrategy();

            $route->prepare($handler, $routeArguments);

            // traverse route middlewares :

            return $route->run($request, $response);

        }
        catch( NotFoundException $e ) {

            $handler = $this->getNotFoundHandler();

            return $handler($request, $response);
        }
        catch( MethodNotAllowedException $e )
        {
            $handler = $this->getNotAllowedHandler();

            return $handler($request, $response, $e->getAllowedMethods());
        }
    }


    /********************************************************************************
     * Route handlers ; HandlerInterface
     *******************************************************************************/


    // route invocation strategy ; found

    public function getRouteInvocationStrategy()
    {
        if( !$this->routeInvocationStrategy )
        {
            $this->routeInvocationStrategy = new RouteInvocationStrategy; // default
        }

        return $this->routeInvocationStrategy;
    }

    public function setRouteInvocationStrategy( RouteInvocationStrategyInterface $handler )
    {
        $this->routeInvocationStrategy = $handler;
    }

    // Not Found

    public function getNotFoundHandler()
    {
        if( !$this->notFoundHandler )
        {
            $this->notFoundHandler = new NotFoundHandler;
        }

        return $this->notFoundHandler;
    }

    public function setNotFoundHandler( callable $handler )
    {
        $this->notFoundHandler = $handler;
    }

    // Method Not Allowed

    public function getNotAllowedHandler()
    {
        if( !$this->notAllowedHandler )
        {
            $this->notAllowedHandler = new NotAllowedHandler;
        }

        return $this->notAllowedHandler;
    }

    public function setHotAllowedHandler( callable $handler )
    {
        $this->notAllowedHandler = $handler;
    }

    // Exceptions ; errors

    public function getExceptionHandler()
    {
        if( !$this->exceptionHandler )
        {
            $this->exceptionHandler = new ExceptionHandler($this->settings['displayErrorDetails']);
        }

        return $this->exceptionHandler;
    }

    public function setExceptionHandler( callable $handler )
    {
        $this->exceptionHandler = $handler;
    }



}
