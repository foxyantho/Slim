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
     * Normalize key in a unified way "key.subkey"
     * 
     * @param  mixed $key
     * @return mixed
     */
    public function normalizeKey( $key )
    {
        return strtolower(str_replace('_', '.', $key));
    }


    /**
     * Get all header extracted from $_SERVER[HTTP_]
     * As opposed to getallheaders Apache function
     * 
     * @return array
     */
    public function getAllHeaders()
    {
        $special = [
            'content.type', 'content.length',
            'auth.type', 'php.auth.user', 'php.auth.pw', 'php.auth.digest'
        ];


        $data = [];

        foreach( $this->all() as $key => $value )
        {
            if( strpos($key, 'http.') === 0 || in_array($key, $special) )
            {
                $data[substr($key, 5)] =  $value;
            }
        }

        return $data;
    }


}
