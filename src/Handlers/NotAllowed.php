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
use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;


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
     * @return ResponseInterface
     */
    public function __invoke( Request $request, Response $response, array $methods = [] )
    {
        $allowed_methods = array_pop($methods);

        if( $methods )
        {
            $allowed_methods = implode(', ', $methods) . ' or ' . $last;
        }

        return $response
                ->status($status)
                ->header('Content-type', $contentType)
                ->header('Allow', implode(', ', $methods))
                ->Write('Method not allowed. Must be one of: ' . $allowed_methods);
    }


}
