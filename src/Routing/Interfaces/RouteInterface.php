<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

namespace Slim\Routing\Interfaces;

use Slim\Http\Interfaces\RequestInterface;
use Slim\Http\Interfaces\ResponseInterface;

/**
 * Route Interface
 *
 * @package Slim
 * @since   3.0.0
 */
interface RouteInterface
{

    /**
     * Dispatch route callable against current Request and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param RequestInterface  $request  The current Request object
     * @param ResponseInterface $response The current Response object
     * @param array             $args     Parsed pattern data
     *
     * @return ResponseInterface
     */
    public function __invoke( RequestInterface $request, ResponseInterface $response );

}
