<?php


namespace Slim\Handlers;


class NotFoundHandler extends AbstractErrorHandler
{

    protected $statusCode = 404;


    protected function extraRenderers()
    {
        return [
            'text/html' => [$this, 'htmlMessage'],
            //'application/json' => [$this, 'jsonMessage'],
            //'text/plain' => PlainTextErrorRenderer::class
        ];
    }

    protected function htmlMessage()
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


}
