<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Http;

use Slim\Collection;
use Slim\Http\Interfaces\EnvironmentInterface;

/**
 * Environment
 *
 * This class decouples the Slim application from the global PHP environment.
 * This is particularly useful for unit testing, but it also lets us create
 * custom sub-requests.
 */
class Environment extends Collection implements EnvironmentInterface
{

    /**
     * Normalize key "key_subkey" ( lowercase )
     * 
     * @param  mixed $key
     * @return mixed
     */
    public function normalizeKey( $key )
    {
        return strtolower($key);
    }

    /**
     * Get all header extracted from $_SERVER[HTTP_]
     * As opposed to getallheaders Apache function
     * 
     * @return array
     */
    public function getHeaders()
    {
        $special = [
            'content_type', 'content_length',
            'auth_type', 'php_auth_user', 'php_auth_pw', 'php_auth_digest'
        ];

        $headers = [];

        foreach( $this->all() as $key => $value )
        {
            if( strpos($key, 'http_') === 0 )
            {
                $key = str_replace('_', '-', $key); // http_xxx -> http-xxx
                
                $headers[substr($key, 5)] =  $value; // strip 'http_'
            }
            elseif( in_array($key, $special) )
            {
                $key = str_replace('_', '-', $key); // http_xxx -> http-xxx

                $headers[$key] =  $value;
            }
        }

        return $headers;
    }


}
