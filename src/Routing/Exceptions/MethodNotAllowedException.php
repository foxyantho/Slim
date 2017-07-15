<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE (MIT License)
 */

namespace Slim\Routing\Exceptions;

use Exception;


class MethodNotAllowedException extends Exception
{

    protected $code = 'PageMethodNotAllowed';

    protected $message = 'Method not allowed';


    /**
     * HTTP methods allowed
     *
     * @var string[]
     */
    protected $allowedMethods;

    /**
     * Create new exception
     *
     * @param string[] $allowedMethods
     */
    public function __construct( array $allowedMethods )
    {
        $this->allowedMethods = $allowedMethods;

        if( !empty($allowedMethods) )
        {
            $this->message .= ', must be one of '.implode(', ', $allowedMethods);
        }

        parent::__construct();
    }

    /**
     * Get allowed methods
     *
     * @return string[]
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }


}
