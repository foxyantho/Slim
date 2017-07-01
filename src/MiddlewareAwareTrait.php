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

use SplStack;
use SplDoublyLinkedList;

use RuntimeException;
use UnexpectedValueException;

/**
 * Middleware
 *
 * This is an internal class that enables concentric middleware layers. This
 * class is an implementation detail and is used only inside of the Slim
 * application; it is not visible to—and should not be used by—end users.
 */
trait MiddlewareAwareTrait
{

    /**
     * Middleware call stack
     * @var  \SplStack
     * @link http://php.net/manual/class.splstack.php
     */
    protected $stack;

    /**
     * Middleware stack lock
     * @var bool
     */
    protected $middlewareLock = false;


    /**
     * Add middleware : prepends new middleware to the application middleware stack.
     *
     * @param callable $callable Any callable that accepts three arguments:
     *                           1. A Request object
     *                           2. A Response object
     *                           3. A "next" middleware callable
     * @return static
     *
     * @throws RuntimeException         If middleware is added while the stack is dequeuing
     * @throws UnexpectedValueException If the middleware doesn't return a Slim\Http\Interfaces\ResponseInterface
     */
    protected function add( callable $callable )
    {
        if( $this->middlewareLock )
        {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }

        if( is_null($this->stack) )
        {
            $this->seedMiddlewareStack();
        }


        $next = $this->stack->top();

        $this->stack[] = function( Request $request, Response $response ) use ( $callable, $next )
        {
            $result = call_user_func($callable, $req, $res, $next);

            if( $result instanceof Response === false )
            {
                throw new UnexpectedValueException('Middleware must return instance of \Slim\Http\ResponseInterface');
            }

            return $result;
        };

        return $this;
    }

    /**
     * Seed middleware stack with first callable
     *
     * @throws RuntimeException if the stack is seeded more than once
     */
    protected function seedMiddlewareStack()
    {
        if( !is_null($this->stack) )
        {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }


        $this->stack = new SplStack; // pile

        $this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO | SplDoublyLinkedList::IT_MODE_KEEP);


        // add kernel

        $this->stack[] = $this;
    }

    /**
     * Call middleware stack
     *
     * @param  Request  $request A request object
     * @param  Response $response A response object
     *
     * @return Response
     */
    public function callMiddlewareStack( Request $request, Response $response )
    {
        if( !isset($this->stack) )
        {
            $this->seedMiddlewareStack();
        }

        $start = $this->stack->top();


        $this->middlewareLock = true;

        $result = $start($request, $response);

        $this->middlewareLock = false;


        return $result;
    }


}
