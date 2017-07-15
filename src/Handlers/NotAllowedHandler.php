<?php


namespace Slim\Handlers;


class NotAllowedHandler extends AbstractErrorHandler
{

    protected $statusCode = 405;


    

    protected function htmlMessage()
    {
        $e = $this->exception;

        $methods = implode(', ', $e->getAllowedMethods());

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

            $methods
        );
    }
}
