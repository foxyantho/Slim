<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
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

use Slim\Handlers\Found as FoundHandler;
use Slim\Handlers\NotFound as NotFoundHandler;
use Slim\Handlers\NotAllowed as NotAllowedHandler;
use Slim\Handlers\Exception as ExceptionHandler;

use Closure;

use Exception;
use Slim\Exceptions\NotFoundException;
use Slim\Exceptions\MethodNotAllowedException;
use Slim\Exceptions\SlimException;

/**
 * App
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Slim Framework application.
 * The \Slim\App class also accepts Slim Framework middleware.
 *
 * @property-read array $settings App settings
 * @property-read EnvironmentInterface $environment
 * @property-read RequestInterface $request
 * @property-read ResponseInterface $response
 * @property-read RouterInterface $router
 * @property-read callable $errorHandler
 * @property-read callable $notFoundHandler function($request, $response)
 * @property-read callable $notAllowedHandler function($request, $response, $allowedHttpMethods)
 */
class Slim
{

    use ResolveCallableTrait;

    use MiddlewareAwareTrait {
        add as addMiddleware;
    }


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


    protected $foundHandler;

    protected $notFoundHandler;

    protected $notAllowedHandler;

    protected $exceptionHandler;


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

        $method = $this->environment['REQUEST_METHOD'];

        $request_headers = new HttpHeaders( getallheaders() );

        $body = file_get_contents('php://input'); // stream_get_contents(fopen('php://input', 'r'));

        $this->request = new HttpRequest($method, $request_headers, $this->environment, $body);


        // response

        $protocolVersion = $this->settings['httpVersion'];

        $response_headers = new HttpHeaders(['Content-Type' => 'text/html']);

        $this->response = ( new HttpResponse(200, $response_headers) )->protocolVersion($protocolVersion);


        // router

        $this->router = new Router;

        $this->router->setUriRoot($this->request->getUriRoot()); // urlfor() stuff


        // handlers

        $this->foundHandler = function() {
            return call_user_func_array(new FoundHandler, func_get_args());
        };

        $this->notFoundHandler = function() {
            return call_user_func_array(new NotFoundHandler, func_get_args());
        };

        $this->notAllowedHandler = function() {
            return call_user_func_array(new NotAllowedHandler, func_get_args());
        };

        $this->exceptionHandler = function() {
            return call_user_func_array(new ExceptionHandler($this->settings['displayErrorDetails']), func_get_args());
        };


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

            'use_rewrite' => true, //@TODO: implementation

            'displayErrorDetails' => true,


            // Templates
    
            'template' => [],

            // Database

            'database' => []
        ];
    }

    /**
     * Set the globally available instance of the app
     * Needed to be instantiated first
     *
     * @return static|null
     */
    public static function getInstance()
    {
        return static::$instance;
    }


    /********************************************************************************
     * Router proxy methods
     *******************************************************************************/


    /**
     * Add GET route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $handler The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function get( $pattern, $handler )
    {
        return $this->map(['GET'], $pattern, $handler);
    }

    /**
     * Add POST route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $handler The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function post( $pattern, $handler )
    {
        return $this->map(['POST'], $pattern, $handler);
    }

    /**
     * Add PUT route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $handler The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function put( $pattern, $handler )
    {
        return $this->map(['PUT'], $pattern, $handler);
    }

    /**
     * Add PATCH route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $handler The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function patch( $pattern, $handler )
    {
        return $this->map(['PATCH'], $pattern, $handler);
    }

    /**
     * Add DELETE route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $handler The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function delete( $pattern, $handler )
    {
        return $this->map(['DELETE'], $pattern, $handler);
    }

    /**
     * Add OPTIONS route
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $handler The route callback routine
     *
     * @return \Slim\Interfaces\RouteInterface
     */
    public function options( $pattern, $handler )
    {
        return $this->map(['OPTIONS'], $pattern, $handler);
    }

    /**
     * Add route for any HTTP method
     *
     * @param  string $pattern  The route URI pattern
     * @param  mixed  $handler The route callback routine
     * @return \Slim\Interfaces\RouteInterface
     */
    public function any( $pattern, $handler )
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $handler);
    }

    /**
     * Add route with multiple methods
     *
     * @param  string[] $methods  Numeric array of HTTP method names
     * @param  string   $pattern  The route URI pattern
     * @param  mixed    $handler The route callback routine
     *
     * @return RouteInterface
     */
    public function map( array $methods, $pattern, $handler )
    {
        $route = $this->router->map($methods, $pattern, $handler);

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

        try
        {
            $response = $this->callMiddlewareStack($this->request, $this->response);
        }
        catch( Exception $exception )
        {
            $response = $this->handleException($this->request, $this->response, $exception);
        }


        $response = $this->finalize($response);


        if( !$silent )
        {
            $this->respond($response);
        }


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
            return $response->withoutHeader('Content-Type')
                            ->withoutHeader('Content-Length');
        }

        // it has body :

        if( $size = $response->getBodyLength() > 0 )
        {
            $response->header('Content-Length', (string) $size);
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
        static $responded = false;

        if( !$responded )
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
                    header(sprintf('%s: %s', $name, $value), false); // don't replace existing
                }
            }

            // Body

            if( !$response->isEmpty() )
            {
                echo $response->getBody();
            }


            $responded = true;
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
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function __invoke( Request $request, Response $response )
    {

        $routeInfo = $this->router->dispatch(
            $request->getMethod(),
            $request->getUriPath()
        );

        // 0 -> type
        // 1 -> Route
        // 2 -> uri arguments

        if( $routeInfo[0] === Router::FOUND )
        {

            // URL decode the named arguments from the router
            // aka dispatchRouterAndPrepareRoute

            $attributes = array_map('urldecode', $routeInfo[2]);

            $request->attributes($attributes);


            // traverse route middlewares :

            list( $request, $response, $handler ) = $routeInfo[1]->run($request, $response);

            // return the response :

            $handler = $this->resolveCallable($handler);


            $foundHandler = $this->foundHandler;

            return $foundHandler($request, $response, $handler);
        }

        if( $routeInfo[0] === Router::NOT_FOUND )
        {
            throw new NotFoundException($request, $response);
        }

        if( $routeInfo[0] === Router::METHOD_NOT_ALLOWED )
        {
            throw new MethodNotAllowedException($request, $response, $routeInfo[1]);
        }
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist, then just re-throw.
     *
     * @param  Exception $e
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface $response
     *
     * @return ResponseInterface
     * @throws Exception if a handler is needed and not found
     */
    protected function handleException( Request $request, Response $response, Exception $e )
    {
        if( $e instanceof NotFoundException )
        {
            $handler = $this->notFoundHandler;

            return $handler($e->getRequest(), $e->getResponse());
        }

        elseif( $e instanceof MethodNotAllowedException )
        {
            $handler = $this->notAllowedHandler;

            return $handler($e->getRequest(), $e->getResponse(), $e->getAllowedMethods());
        }

        elseif( $e instanceof SlimException )
        {
            // this is a Stop exception and contains the 

            return $e->getResponse();
        }

        // other exception, use $request and $response params

        $handler = $this->exceptionHandler;

        return $handler($request, $response, $e);
    }


}
