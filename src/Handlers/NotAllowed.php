<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

namespace Slim\Handlers;

use Slim\Handlers\Interfaces\HandlerInterface;
use Slim\Http\Interfaces\RequestInterface;
use Slim\Http\Interfaces\ResponseInterface;


/**
 * Default not allowed handler
 *
 * This is the default Slim application error handler. All it does is output
 * a clean and simple HTML page with diagnostic information.
 */
class NotAllowed implements HandlerInterface
{

    /**
     * Invoke error handler
     *
     * @param  RequestInterface  $request   The most recent Request object
     * @param  ResponseInterface $response  The most recent Response object
     * @param  string[]          $methods   Allowed HTTP methods
     *
     * @return ResponseInterface
     */
    public function __invoke( RequestInterface $request, ResponseInterface $response, array $methods = [] )
    {
        return $response
                ->status(405)
                ->header('Content-type', 'text/html')
                ->header('Allow', implode(', ', $methods))
                ->Write('Method not allowed. Must be one of: ' . $this->allowedMethodsAsString($methods));
    }

    /**
     * Return the allowed methods as a string
     * example: "get, post or put"
     * 
     * @param  array  $methods
     * @return string
     */
    protected function allowedMethodsAsString( array $methods )
    {
        $last = array_pop($methods);

        if( $methods )
        {
            return implode(', ', $methods) . ' or ' . $last;
        }

        return $last;
    }


}
