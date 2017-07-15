<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Handlers;


use Slim\Handlers\Interfaces\ErrorHandlerInterface;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use Exception;
use Slim\Routing\Exceptions\NotFoundException;
use Slim\Routing\Exceptions\MethodNotAllowedException;

use Slim\Handlers\ErrorRenderers\HtmlErrorRenderer;
use Slim\Handlers\ErrorRenderers\JsonErrorRenderer;
use Slim\Handlers\ErrorRenderers\PlainTextErrorRenderer;
use Slim\Handlers\ErrorRenderers\XmlErrorRenderer;


class AbstractErrorHandler implements ErrorHandlerInterface
{

    /**
     * Known handled content types
     * @var array
     */
    protected $knownContentTypes = [
        'text/html',
        'application/json',
        'text/plain',
        'application/xml',
        'text/xml'
    ];

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Exception
     */
    protected $exception;

    /**
     * @var bool
     */
    protected $displayErrorDetails;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * mressage renderers
     * @var array
     */
    protected $renderers = [];

    /**
     * Current message renderer
     * @var mixed
     */
    protected $renderer;


    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request   The most recent Request object
     * @param ResponseInterface      $response  The most recent Response object
     * @param Exception|Throwable    $exception The caught Exception object
     * @param bool $displayErrorDetails Whether or not to display the error details
     *
     * @return Response
     */
    public function __invoke( Request $request, Response $response, Exception $exception, $displayErrorDetails = false )
    {
        $this->request = $request;

        $this->response = $response;

        $this->exception = $exception;

        $this->displayErrorDetails = $displayErrorDetails;

        // renderers :

        $this->renderers = array_merge(static::getDefaultRenderers(), $this->extraRenderers());

        // send response :

        return $this->formatResponse();
    }



    /********************************************************************************
     * Rendering
     *******************************************************************************/


    /**
     * Return the default message renderers
     *
     * @return array
     */
    protected static function getDefaultRenderers()
    {
        return [
            'text/html' => HtmlErrorRenderer::class,
            'application/json' => JsonErrorRenderer::class,
            'text/plain' => PlainTextErrorRenderer::class,
            // 'text/xml' => XmlErrorRenderer::class,
            // 'application/xml' => XmlErrorRenderer::class
        ];
    }

    /**
     * Extra renderers if needed in child classes
     *
     * @return array
     */
    protected function extraRenderers()
    {
        return [];
    }

    /**
     * Return the default renderer
     *
     * @return mixed
     */
    protected function getDefaultRenderer()
    {
        return [HtmlErrorRenderer::class];
    }

    /**
     * @return Response
     */
    protected function formatResponse()
    {
        $e = $this->exception;

        $response = $this->response;

        // renderer callable

        $renderer = $this->getRenderer(); // todo: refactor

        if( is_callable($renderer) )
        {
            $body = call_user_func_array($renderer, [$this->exception, $this->displayErrorDetails]);
        }
        elseif( is_string($renderer) ) // class
        {
            $renderer = new $renderer($this->exception, $this->displayErrorDetails);

            $body = $renderer();
        }

        // send response :

        if( $e instanceof MethodNotAllowedException )
        {
            $response->header('allow', $e->getAllowedMethods());
        }

        return $response
            ->status($this->getStatusCode())
            ->header('content-type', $this->getContentType())
            ->write($body);
    }

    /**
     * Determine which renderer to use based on content type
     * Overloaded $renderer from calling class takes precedence over all
     *
     * @throws \RuntimeException
     */
    protected function getRenderer()
    {
        if( !isset($this->renderer) )
        {
            $contentType = $this->getContentType();

            if( isset($this->renderers[$contentType]) )
            {
                $renderer = $this->renderers[$contentType];
            }
            else
            {
                $renderer = $this->getDefaultRenderer();
            }

            $this->renderer = $renderer;
        }

        return $this->renderer;
    }



    /********************************************************************************
     * Gets/Sets methods
     *******************************************************************************/


    /**
     * @return int
     */
    public function getStatusCode()
    {
        if( !isset($this->statusCode) )
        {
            $statusCode = 500; // default

            $this->statusCode = $statusCode;
        }

        return $this->statusCode;
    }

    /**
     * Determine which content type we know about is wanted using Accept header
     *
     * Note: This method is a bare-bones implementation designed specifically for
     * Slim's error handling requirements. Consider a fully-feature solution such
     * as willdurand/negotiation for any other situation.
     *
     * @return string
     */
    public function getContentType()
    {
        if( !isset($this->contentType) )
        {
            $acceptHeader = $this->request->getHeader('accept');

            $selectedContentTypes = array_intersect(explode(',', $acceptHeader), $this->knownContentTypes);

            $count = count($selectedContentTypes);

            $contentType = 'text/html'; // default

            if( $count )
            {
                $current = current($selectedContentTypes);

                $contentType = $current;

                // Ensure other supported content types take precedence over text/plain
                // when multiple content types are provided via Accept header.

                if( $current === 'text/plain' && $count > 1 )
                {
                    $contentType = next($selectedContentTypes);
                }
            }

            $this->contentType = $contentType;
        }

        return $this->contentType;
    }



    /********************************************************************************
     * Helpers
     *******************************************************************************/


    /**
     * Wraps the error_log function so that this can be easily tested
     *
     * @param string $error
     */
    protected function logError( $error )
    {
        error_log($error, 0);
    }


}
