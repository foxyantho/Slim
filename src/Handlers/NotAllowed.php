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


/**
 * Default not allowed handler
 *
 * This is the default Slim application error handler. All it does is output
 * a clean and simple HTML page with diagnostic information.
 */
class NotAllowed implements HandlerInterface
{

    /**
     * Invoke error handler
     *
     * @param  RequestInterface  $request   The most recent Request object
     * @param  ResponseInterface $response  The most recent Response object
     * @return ResponseInterface
     */
    public function __invoke( Request $request, Response $response, array $methods = [] )
    {

        $contentType = $this->determineContentType($request->getHeader('Accept'));

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
                ->header('Content-type', $contentType)
                ->header('Allow', implode(', ', $methods))
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

    protected function renderHtmlMessage( array $methods = [] )
    {
        $allowed_methods = array_pop($methods);

        if( $methods )
        {
            $allowed_methods = implode(', ', $methods) . ' or ' . $last;
        }

        return 'Method not allowed. Must be one of : ' . $allowed_methods;
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
