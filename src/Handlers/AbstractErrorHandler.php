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
use UnexpectedValueException;
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
        'text/plain',
        'application/json',
        'text/html',
        'text/xml',
        'application/xml',
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
     * Invoke error handler
     *
     * @param ServerRequestInterface $request   The most recent Request object
     * @param ResponseInterface      $response  The most recent Response object
     * @param Exception|Throwable    $exception The caught Exception object
     * @param bool $displayErrorDetails Whether or not to display the error details
     *
     * @return Response
     */
    public function __invoke( Request $request, Response $response, $exception, $displayErrorDetails = false )
    {

        $this->request = $request;

        $this->response = $response;

        $this->exception = $exception;

        $this->displayErrorDetails = $displayErrorDetails;

        // content type based on client

        $contentType = $this->determineContentType();

        $this->contentType = $contentType;

        // redering message :

        $output = $this->renderMessage($contentType);

        // send response :

        if( $exception instanceof MethodNotAllowedException )
        {
            $response->header('allow', $exception->getAllowedMethods());
        }

        return $response
            ->status($this->getStatusCode())
            ->header('content-type', $this->getContentType())
            ->write($output);
    }



    /********************************************************************************
     * Rendering
     *******************************************************************************/


    protected function renderMessage( $contentType )
    {
        $output = null;

        switch( $contentType )
        {
            case 'text/plain':
                $output = $this->renderText();
            break;

            case 'application/json':
                $output = $this->renderJson();
            break;

            case 'text/html':
                $output = $this->renderHtml();
            break;

            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXml();
            break;

            // default:
            //     throw new UnexpectedValueException('Cannot render unknown content type ' . $contentType);
        }

        return $output;
    }

    protected function renderWithClass( $className )
    {
        $renderer = new $className($this->exception, $this->displayErrorDetails);

        return $renderer(); // call
    }

    protected function renderText()
    {
        return $this->renderWithClass(PlainTextErrorRenderer::class);
    }

    protected function renderJson()
    {
        return $this->renderWithClass(JsonErrorRenderer::class);
    }

    protected function renderHtml()
    {
        return $this->renderWithClass(HtmlErrorRenderer::class);
    }

    protected function renderXml()
    {
        return $this->renderWithClass(XmlErrorRenderer::class);
    }


    /********************************************************************************
     * Gets/Sets methods
     *******************************************************************************/


    /**
     * Return status code ; will be sent to the client
     * 
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
     * Return content type ; will be sent to the client
     *
     * @return string|null
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Try to determine which content type we know about is wanted using Accept header
     *
     * Note: This method is a bare-bones implementation designed specifically for
     * Slim's error handling requirements. Consider a fully-feature solution such
     * as willdurand/negotiation for any other situation.
     *
     * @return string
     */
    protected function determineContentType()
    {
        // try to get client content type :

        $contentType = 'text/html'; // default, can be overrided in child class

        $acceptHeader = $this->request->getHeader('accept');

        $selectedContentTypes = array_intersect(explode(',', $acceptHeader), $this->knownContentTypes);

        $count = count($selectedContentTypes);

        if( $count )
        {
            $current = current($selectedContentTypes);

            $contentType = $current;

            // Ensure other supported content types take precedence over text/plain
            // when multiple content types are provided via Accept header.

            if( $current == 'text/plain' && $count > 1 )
            {
                $contentType = next($selectedContentTypes);
            }
        }

        return $contentType;
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
