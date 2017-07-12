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
 * Default not found handler
 *
 * This is the default Slim application not found handler. All it does is output
 * a clean and simple HTML page with diagnostic information.
 */
class NotFound implements HandlerInterface
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
     * Invoke not found handler
     *
     * @param  RequestInterface  $request
     * @param  ResponseInterface $response
     * @return ResponseInterface
     */
    public function __invoke( Request $request, Response $response )
    {
        $contentType = $this->determineContentType($request);

        switch( $contentType )
        {
            case 'text/html':
                $output = $this->renderHtmlMessage();
            break;

            case 'application/json':
                $output = $this->renderJsonMessage();
            break;

            default:
                $output = 'NotFound';
            break;
        }


        return $response->status(404)
                        ->header('content-type', $contentType)
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

    protected function renderHtmlMessage()
    {
        return '<html>
                <head>
                    <title>Page Not Found</title>
                    <style>
                        body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}
                        h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}
                        strong{display:inline-block;width:65px;}
                    </style>
                </head>
                <body>
                    <h1>Page Not Found</h1>
                    <p>
                        The page you are looking for could not be found. Check the address bar
                        to ensure your URL is spelled correctly. If all else fails, you can
                        visit our home page at the link below.
                    </p>
                    <a href="/">Visit the Home Page</a>
                </body>
                </html>';
    }

    protected function renderJsonMessage()
    {
        return json_encode([

            'error' => 
            [
                'code' => 404,

                'type' => 'NotFound',

                'message' => 'Page not found'
            ]
        ]);
    }


}
