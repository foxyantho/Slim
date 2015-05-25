<?php


namespace Slim\Handlers\Interfaces;

use Slim\Http\Interfaces\RequestInterface;
use Slim\Http\Interfaces\ResponseInterface;


interface HandlerInterface
{

    public function __invoke( RequestInterface $request, ResponseInterface $response );


}
