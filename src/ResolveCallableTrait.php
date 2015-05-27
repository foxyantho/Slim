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

use Closure;

use RuntimeException;


/**
 * ResolveCallable
 *
 * This is an internal class that enables resolution of 'class:method' strings
 * into a closure. This class is an implementation detail and is used only inside
 * of the Slim application.
 */
trait ResolveCallableTrait
{

    /**
     * Resolve a string of the format 'class:method' into a closure that the router can dispatch.
     *
     * @param  string $callable
     * @return \Closure|RuntimeException
     */
    protected function resolveCallable( $callable )
    {
        if( is_callable($callable) )
        {
            return $callable;
        }

        if( $callable instanceof Closure )
        {
            return $callable->bindTo($this);
        }

        if( is_string($callable) && strpos($callable, ':') )
        {
            // check if a controller ( \Class:function )

            if( preg_match('!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!', $callable, $matches) )
            {
                // wrap it into a closure

                $class = $matches[1];
                $method = $matches[2];

                return function( RequestInterface $request, ResponseInterface $response ) use ( $class, $method )
                {
                    $handler = new $class($request, $response);

                    // first two arguments are always req & res
                    return call_user_func_array([$handler, $method], array_slice(func_get_args(), 2));
                };
            }

            throw new RuntimeException('Callable "' . $callable . '" is not resolvable');
        }

        throw new RuntimeException('Callable is not resolvable');
    }


}
