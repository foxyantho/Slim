<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Handlers\Interfaces;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;


/**
 * ErrorHandlerInterface
 *
 * @package Slim
 * @since   4.0.0
 */
interface ErrorHandlerInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Exception|Throwable $exception
     * @param $displayErrorDetails
     * @return ResponseInterface
     */
    public function __invoke( Request $request, Response $response, $exception, $displayErrorDetails );


}
