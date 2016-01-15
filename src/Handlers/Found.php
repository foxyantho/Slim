<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

use Slim\Handlers\Interfaces\HandlerInterface;
use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use Exception;

/**
 * Route callback strategy with route parameters as individual arguments.
 */
class Found implements HandlerInterface
{
    /**
     * Invoke a route callable with request, response and all route parameters
     * as individual arguments.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $callable
     * @param array                  $parameters Route params
     *
     * @return mixed
     */
    public function __invoke( Request $request, Response $response, $handler = null, array $parameters = [] )
    {
        // invoke route callable

        try
        {
            ob_start();

            $newResponse = call_user_func_array($handler, [ $request, $response ] + $parameters);

            // @TODO: prefering using return response
            $output = ob_get_clean();

        }
        catch( Exception $e )
        {
            ob_end_clean();

            throw $e;
        }

        // if route callback returns a ResponseInterface, then use it

        if( $newResponse instanceof Response )
        {
            $response = $newResponse;
        }

        // if route callback retuns a string, then append it to the response

        if( is_string($newResponse) )
        {
            $response->write($newResponse);
        }

        // append output buffer content if there is any

        if( $output )
        {
            $response->write($output);
        }

        // nothing, return original response

        return $response;
    }


}
