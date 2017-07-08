<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim;

use Closure;

use RuntimeException;


/**
 * ResolveCallable
 *
 * This is an internal class that enables resolution of '\class:method' strings
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

        if( $callable instanceof Closure ) // closures
        {
            // if ( isset($this->container) )
            // {
            //     return $callable->bindTo($this->container);
            // }

            // return $callable->bindTo($this); //todo
        }

        if( is_callable($callable) ) // functions
        {
            return $callable;
        }

        if( is_string($callable) ) // "\Class:method" or \Class::class
        {
            $class = $callable;
            $method = '__invoke';

            if( strpos($callable, ':') )
            {
                list($class, $method) = explode(':', $callable, 2);
            }

            // call the resolved :

            $resolved = [new $class, $method];

            if( !is_callable($resolved) )
            {
                throw new RuntimeException(sprintf('Callable "%s" does not exist', $callable));
            }

            return $resolved; // function() { call_user_func_array($resolved, func_get_args()); };
        }

        throw new RuntimeException(sprintf('Callable "%s" is not resolvable', $callable));
    }


}
