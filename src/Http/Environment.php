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
     * Create mock environment
     *
     * @param  array $userSettings Array of custom environment keys and values
     * @return self
     */
    public static function mock( array $userSettings = [] )
    {
        return array_merge([
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'SCRIPT_NAME'          => '',
            'REQUEST_URI'          => '/',
            'QUERY_STRING'         => '',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3',
            'HTTP_USER_AGENT'      => 'Slim Framework',
            'REMOTE_ADDR'          => '127.0.0.1',
            'REQUEST_TIME'         => time(),
            'REQUEST_TIME_FLOAT'   => microtime(true),
        ], $userSettings);
    }


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
