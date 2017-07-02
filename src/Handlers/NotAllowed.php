<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Handlers;

use Slim\Handlers\Interfaces\HandlerInterface;
use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;


/**
 * Default not allowed handler
 *
 * This is the default Slim application error handler. All it does is output
 * a clean and simple HTML page with diagnostic information.
 */
class NotAllowed implements HandlerInterface
{

    /**
     * Known handled content types
     *
     * @var array
     */
    protected $knownContentTypes = [
        'text/html',
        'application/json'
    ];

    /**
     * Invoke error handler
     *
     * @param  RequestInterface  $request   The most recent Request object
     * @param  ResponseInterface $response  The most recent Response object
     * @return ResponseInterface
     */
    public function __invoke( Request $request, Response $response, array $methods = [] )
    {

        $contentType = $this->determineContentType($request);

        switch( $contentType )
        {
            case 'text/html':
                $output = $this->renderHtmlMessage($methods);
            break;

            case 'application/json':
                $output = $this->renderJsonMessage($methods);
            break;

            default:
                $output = 'NotAllowed';
            break;
        }


        return $response
                ->status(405)
                ->header('content.type', $contentType)
                ->header('allow', implode(', ', $methods))
                ->write($output);
    }

    /**
     * Determine which content type we know about is wanted using Accept header
     *
     * @param RequestInterface $request
     * @return string
     */
    private function determineContentType( Request $request)
    {
        $list = explode(',', $request->getHeader('accept'));

        foreach( $list as $type )
        {
            if( in_array($type, $this->knownContentTypes) )
            {
                return $type;
            }
        }

        return 'text/html';
    }

    protected function renderHtmlMessage( array $methods = [] )
    {
        $allowed_methods = array_pop($methods);

        if( $methods )
        {
            $allowed_methods = implode(', ', $methods) . ' or ' . $last;
        }

        return sprintf(
            "<html>
                <head>
                    <title>Method not allowed</title>
                    <style>
                        body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}
                        h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}
                    </style>
                </head>
                <body>
                    <h1>Method not allowed</h1>
                    <p>Method not allowed. Must be one of: <strong>%s</strong></p>
                </body>
            </html>",

            $allowed_methods
        );
    }

    protected function renderJsonMessage( array $methods = [] )
    {
        return json_encode([

            'error' => 
            [
                'code' => 405,

                'type' => 'NotAllowed',

                'message' => 'Method not allowed',


                'allowed' => $methods
            ]
        ]);
    }


}
