<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

namespace Slim\Handlers;

use Slim\Handlers\Interfaces\HandlerInterface;
use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use Exception as BaseException;

/**
 * Default error handler
 *
 * This is the default Slim application error handler. All it does is output
 * a clean and simple HTML page with diagnostic information.
 */
class Exception implements HandlerInterface
{

    /**
     * Invoke error handler
     *
     * @param  RequestInterface  $request   The most recent Request object
     * @param  ResponseInterface $response  The most recent Response object
     * @param  \Exception        $exception The caught Exception object
     * @return ResponseInterface
     */
    public function __invoke( Request $request, Response $response, BaseException $exception = null )
    {

        $contentType = $this->determineContentType($request->getHeader('Accept'));

        switch( $contentType )
        {
            case 'text/html':
                $output = $this->renderHtmlMessage($exception);
            break;

            case 'application/json':
                $output = $this->renderJsonMessage($exception);
            break;

            default:
                $output = 'NotFound';
            break;
        }


        return $response->status(500)
                        ->header('Content-Type', $contentType)
                        ->write($output);
    }

    /**
     * Read the accept header and determine which content type we know about
     * is wanted.
     *
     * @param  string $acceptHeader Accept header from request
     * @return string
     */
    protected function determineContentType( $acceptHeader )
    {
        $list = explode(',', $acceptHeader);

        $known = ['text/html', 'application/json'];
        
        foreach( $list as $type )
        {
            if( in_array($type, $known) )
            {
                return $type;
            }
        }

        return 'text/html';
    }

    protected function renderHtmlMessage( BaseException $exception )
    {
        $html = '<p>The application could not run because of the following error:</p>';
        $html .= '<h2>Details</h2>';

        $html .= $this->renderHtmlException($exception);

        while( $exception = $exception->getPrevious() )
        {
            $html .= '<h2>Previous exception</h2>';

            $html .= $this->renderHtmlException($exception);
        }

        return sprintf(
            "<html>
                <head>
                    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
                    <title>Application Error</title>
                    <style>
                        body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}
                        h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}
                        strong{display:inline-block;width:65px;}
                    </style>
                </head>
                <body>
                    <h1>Application Error</h1>
                    %s
                </body>
            </html>",

            $html
        );

    }

    /**
     * Render exception as html.
     *
     * @param Exception $exception
     * @return string
     */
    private function renderHtmlException( BaseException $exception )
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();

        $trace = str_replace(['#', '\n'], ['<div>#', '</div>'], $exception->getTraceAsString());

        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        if( $code )
        {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }
        if( $message )
        {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', $message);
        }
        if( $file )
        {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }
        if( $line )
        {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }
        if( $trace )
        {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', $trace);
        }

        return $html;
    }

    protected function renderJsonMessage( BaseException $exception )
    {
        return json_encode([

            'error' => 
            [
                'code' => 500,

                'type' => 'Exception',

                'message' => 'Application Error'
            ]
        ]);
    }


}
