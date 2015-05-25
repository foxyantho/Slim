<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

namespace Slim\Http;

use Slim\Collection;
use Slim\Http\Interfaces\HeadersInterface;
use Slim\Http\Interfaces\EnvironmentInterface;

/**
 * Headers
 *
 * This class represents a collection of HTTP headers
 * that is used in both the HTTP request and response objects.
 * It also enables header name case-insensitivity when
 * getting or setting a header value.
 *
 * Each HTTP header can have multiple values. This class
 * stores values into an array for each header name. When
 * you request a header value, you receive an array of values
 * for that header.
 */
class Headers extends Collection implements HeadersInterface
{

    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     * @var array
     */
    protected static $special = [
        'CONTENT_TYPE', 'CONTENT_LENGTH', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE'
    ];

    /**
     * Create new headers collection with data extracted from
     * the application Environment object
     *
     * @param  Environment $environment
     * @return self
     */
    public static function createFromEnvironment( EnvironmentInterface $environment )
    {
        $headers = [];

        foreach( $environment as $key => $value )
        {
            $key = strtoupper($key);

            if( strpos($key, 'HTTP_') === 0 || in_array($key, static::$special) )
            {
                if( $key !== 'HTTP_CONTENT_LENGTH' )
                {
                    // replace '_' by ' ', ucwords, re-replace ' ' by '-'
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
                }
            }
        }

        return new static($headers);
    }


}
