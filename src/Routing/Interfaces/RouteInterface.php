<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing\Interfaces;

use Slim\Routing\Interfaces\RouteInvocationStrategyInterface;

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

    public function __invoke( RequestInterface $request, ResponseInterface $response );


    public function getInvocationStrategy();

    public function setInvocationStrategy( RouteInvocationStrategyInterface $handler );

}
