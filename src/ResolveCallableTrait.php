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

use Closure;

use RuntimeException;


/**
 * ResolveCallable
 *
 * This is an internal class that enables resolution of '\class@method' strings
 * into a closure. This class is an implementation detail and is used only inside
 * of the Slim application.
 */
trait ResolveCallableTrait
{

    /**
     * Resolve a string of the format '\class@method' into a closure that the router can dispatch.
     *
     * @param  string $callable
     * @return \Closure
     * @throws RuntimeException if the string cannot be resolved as a callable
     */
    protected function resolveCallable( $callable )
    {

        if( $callable instanceof Closure )
        {
            return $callable->bindTo($this);
        }

        if( is_callable($callable) )
        {
            return $callable;
        }

        if( is_string($callable) && strpos($callable, '@') )
        {
            // check if a controller ( \class@method )

            if( preg_match('#^([^@]+)@([a-zA-Z0-9_]+)$#', $callable, $matches) )
            {
                // wrap it into a closure

                $class = $matches[1];
                $method = $matches[2];

                return function( Request $request, Response $response ) use ( $class, $method )
                {
                    // first two arguments are always req & res

                    return call_user_func_array([new $class, $method], func_get_args());
                };
            }

        }

        throw new RuntimeException('Callable "' . $callable . '" is not resolvable');
    }


}
