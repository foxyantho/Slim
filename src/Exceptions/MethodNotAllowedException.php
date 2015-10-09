<?php

namespace Slim\Exceptions;


use Slim\Http\Interfaces\ResponseInterface as Response;


class MethodNotAllowedException extends SlimException
{

    protected $allowedMethods;


    /**
     * Create new exception
     *
     * @param ResponseInterface $response
     */
    public function __construct( Response $response, array $allowedMethods )
    {
        parent::__construct($response);

        $this->allowedMethods = $allowedMethods;
    }

    /**
     * Get allowed methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }


}
