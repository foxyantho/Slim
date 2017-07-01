<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Exceptions;

use Exception;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

/**
 * Stop Exception
 *
 * This Exception is thrown when the Slim application needs to abort
 * processing and return control flow to the outer PHP script.
 */
class SlimException extends Exception
{

    /**
     * A request object
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * A response object to send to the HTTP client
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Create new exception
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct( Request $request, Response $response )
    {
        parent::__construct();


        $this->request = $request;

        $this->response = $response;
    }

    /**
     * Get request
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }


}