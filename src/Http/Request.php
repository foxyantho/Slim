<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Http;

use Slim\Http\Interfaces\RequestInterface;
use Slim\Http\Interfaces\HeadersInterface;
use Slim\Http\Interfaces\EnvironmentInterface;

use Closure;

use Slim\Collection;

use InvalidArgumentException;
use RuntimeException;

/**
 * Response
 *
 * This class represents an HTTP response. It manages
 * the response status, headers, and body
 * according to the PSR-7 standard.
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/MessageInterface.php
 * @link https://github.com/php-fig/http-message/blob/master/src/RequestInterface.php
 */
class Request implements RequestInterface
{

    /**
     * The request protocol version
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * The request method
     * @var string
     */
    protected $method;

    /**
     * The request headers
     * @var \Slim\Http\Interfaces\HeadersInterface
     */
    protected $headers;

    /**
     * server environment variables at the time the request was created.
     * @var array
     */
    protected $serverParams;

    /**
     * the request body object
     * @var string
     */
    protected $body;

    /**
     * List of request body parsers (e.g., url-encoded, JSON, XML, multipart)
     * @var callable[]
     */
    protected $bodyParsers = [];

    /**
     * The request query params
     * @var array
     */
    protected $queryParams;

    /**
     * The request body params
     * @var array
     */
    protected $bodyParams;

    /**
     * The request attributes
     * @var \Slim\Collection
     */
    protected $attributes;

    /**
     * Valid request methods
     * @var string[]
     */
    protected $validMethods = [
        'GET', 'POST', 'CONNECT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH', 'PUT', 'TRACE'
    ];


    /**
     * Create new HTTP request.
     *
     * @param string                $method
     * @param HeadersInterface      $headers
     * @param EnvironmentInterface  $serverParams
     * @param mixed                 $body
     */
    public function __construct( $method, HeadersInterface $headers, EnvironmentInterface $serverParams, $body )
    {
        $this->method = $this->filterMethod($method); // @FIXME:wrap exception ; handle exception if not know

        $this->headers = $headers;

        $this->serverParams = $serverParams;

        $this->body = $body;

        // protocol version
        
        if( isset($serverParams['server.protocol']) )
        {
            $this->protocolVersion = str_replace('HTTP/', '', $serverParams['server.protocol']);
        }

        // route attributes

        $this->attributes = new Collection;

        // body params parsers :

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function( $input )
        {
            parse_str(urldecode($input), $data); // if not argment#2 -> extract()

            return $data;
        });

        $this->registerMediaTypeParser('application/json', function( $input )
        {
            return json_decode($input, true); // as array
        });
/*
        $this->registerMediaTypeParser(['text/xml', 'application/xml'], function( $input )
        {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);

            return $result;
        });
*/

        // @TODO enctype="multipart/form-data" for upload form
    }


    /*******************************************************************************
     * Protocol
     ******************************************************************************/


    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }


    /*******************************************************************************
     * Method
     ******************************************************************************/


    /**
     * Get the HTTP request method
     *
     * This method returns the HTTP request's method, and it
     * respects override values specified in the `X-Http-Method-Override`
     * request header or in the `_METHOD` body parameter.
     *
     * @return string
     */
    public function getMethod()
    {
        if( empty($this->method) ) // method override via header
        {
            if( $customMethod = $this->getHeader('x.http.method.override') )
            {
                $this->method = $this->filterMethod($customMethod);
            }
        }

        return $this->method;
    }

    /**
     * Validate the HTTP method
     *
     * @param  null|string $method
     * @return null|string
     * @throws \InvalidArgumentException on invalid HTTP method.
     */
    protected function filterMethod( $method )
    {
        if( !is_string($method) )
        {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method ; must be a string, received %s',
                ( is_object($method) ? get_class($method) : gettype($method) )
            ));
        }


        $method = strtoupper($method);

        if( !in_array($method, $this->validMethods) )
        {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }


        return $method;
    }

   /**
     * Does this request use a given method?
     *
     * @param  string $method
     * @return bool
     */
    public function isMethod( $method )
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Is this a GET request?
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Is this a POST request?
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Is this a PUT request?
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * Is this a PATCH request?
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is this a DELETE request?
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is this a HEAD request?
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Is this a OPTIONS request?
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Is this an XHR request?
     *
     * @return bool
     */
    public function isXhr()
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }


    /*******************************************************************************
     * Headers
     ******************************************************************************/


    /**
     * Retrieves all message headers.
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers
     *     foreach ($message->getHeaders() as $name => $values) {
     *         $headers[$name] = explode(',', $values);
     *     }
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param  string $name Case-insensitive header field name.
     * @return bool
     */
    public function hasHeader( $name )
    {
        return $this->headers->has($name);
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param  string   $name Case-insensitive header field name.
     * @return string|null
     */
    public function getHeader( $name )
    {
        return $this->headers->get($name);
    }












    /**
     * Get request content type
     * "application/json;charset=utf8;foo=bar"
     *
     * @return string|false The request content type, if known
     */
    public function getContentType()
    {
        return $this->getHeader('content.type');
    }

    /**
     * Get request media type, if known.
     * "application/json;charset=utf8;foo=bar" -> "application/json"
     *
     * @return string|null The request media type, minus content-type params
     */
    public function getMediaType()
    {
        if( $contentType = $this->getContentType() )
        {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * Get request content type media params, if known
     * "application/json;charset=utf8;foo=bar" -> ['charset' => 'utf8', 'foo' => 'bar']
     *
     * @return array
     */
    public function getContentTypeParams()
    {
        if( $contentType = $this->getHeader('Content-Type') )
        {
            $params = [];

            $parts = preg_split('/\s*[;,]\s*/', $contentType);

            
            foreach( array_slice($parts, 1) as $part ) // first is content-type : not needed
            {
                $conf = explode('=', $part);

                $params[strtolower($conf[0])] = $conf[1];
            }

            return $params;
        }

        return false;
    }

    /**
     * Get request content length, if known
     *
     * @return int|null
     */
    public function getContentLength()
    {
        if( $result = $this->getHeader('content.length') )
        {
            return (int) $result;
        }

        return false;
    }

// @TODO: cookies ?


    /*******************************************************************************
     * Uri
     ******************************************************************************/


    /**
     * Retrieve the authority portion of the URI.
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @return string
     */
    public function getUriAuthority()
    {
        // Authority: Host

        if( $this->serverParams['http.x.forwarded.host'] ) // proxy
        {
            return trim(current(explode(',', $this->serverParams['http.x.forwarded_host'])));
        }
        
        if( $this->serverParams['http.host'] ) // http header
        {
            return $this->serverParams['http.host'];
        }
        
        return $this->serverParams['server.name'];
    }

    /**
     * Retrieve the path segment of the URI: the folder from where the project is running.
     * If there is no path ( load from root folder -> equal "." ) return "/".
     * 
     * @return string
     */
    public function getUriBasePath()
    {
        $basePath = dirname($this->serverParams['script.name']); // "/"index.php, "/folder/"index.php

        return $basePath !== '.' ?

            sprintf('/%s/', trim($basePath, '/'))   :   '/';
    }

    /**
     * Retrieve the path segment of the URI. ("/route/welcome")
     * This method MUST return a string; if no path is present it MUST return the string "/".
     *
     * @return string
     */
    public function getUriPath()
    {
        $requestUri = urldecode($this->serverParams['request.uri']);

        $requestScriptName = $this->serverParams['script.name']; // with base folder


        if( strpos($requestUri, $requestScriptName) === 0 )
        {
            // no rewrite "/folder/index.php/route"

            $requestUri = substr($requestUri, strlen($requestScriptName));
        }
        else
        {
            // remove base folder "'/folder/'index.php/route" or "'/folder/'route"

            if( strpos($requestUri, $basePath = $this->getUriBasePath()) === 0 )
            {
                $requestUri = substr($requestUri, strlen($basePath));
            }
        }

        return '/' . ltrim($requestUri, '/');
    }

    /**
     * Get website's root uri ( //website/folder/ ) with ommiting http scheme
     * 
     * @return string
     */
    public function getUriRoot()
    {
        return '//' . $this->getUriAuthority() . $this->getUriBasePath();
    }


    /*******************************************************************************
     * Server Params
     ******************************************************************************/


    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }


    /*******************************************************************************
     * Attributes
     ******************************************************************************/


    /**
     * Retrieve attributes derived from the request.
     * The request "attributes" may be used to pass parameters in middlewares
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes->all();
    }

    /**
     * Retrieve a single derived request attribute.
     * If the attribute has not been previously set, returns the default value as provided.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getAttribute( $name, $default = null )
    {
        return $this->attributes->get($name, $default);
    }

    /**
     * Allows setting a single derived request
     *
     * @see    getAttributes()
     * @param  string $name The attribute name.
     * @param  mixed  $value The value of the attribute.
     * @return self
     */
    public function attribute( $name, $value )
    {
        $this->attributes->set($name, $value);

        return $this;
    }

    /**
     * Allows setting all new derived request attributes
     *
     * @param  array $attributes New attributes
     * @return self
     */
    public function attributes( array $attributes )
    {
        $this->attributes = new Collection($attributes);

        return $this;
    }

    /**
     * Removing a single derived request attribute
     *
     * @param  string $name
     * @return self
     */
    public function withoutAttribute( $name )
    {
        $this->attributes->remove($name);

        return $this;
    }


    /*******************************************************************************
     * Query Params
     ******************************************************************************/



    /**
     * Retrieve query string arguments : the deserialized query string arguments, if any.
     * aka $_GET
     *
     * @return array
     */
    public function getQueryParams()
    {
        $this->buildQueryParams();

        return $this->queryParams;
    }

    /**
     * Lazy-load: Build the query params and send them into their array
     *
     * @return array|null
     * @throws RuntimeException
     */
    protected function buildQueryParams()
    {
        if( empty($this->queryParams) )
        {
            // parse query string 'x=x&y[]=y'

            $queryString = urldecode($this->serverParams['query.string']);

            parse_str($queryString, $this->queryParams); // send URL decodes in $queryParams
        }
    }

    /**
     * Retrieve a query parameter provided in the request body.
     * aka $_GET
     * 
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function query( $key, $default = null )
    {
        $this->buildQueryParams();

        return isset($this->queryParams[$key]) ? $this->queryParams[$key] : $default;
    }


    /*******************************************************************************
     * Body
     ******************************************************************************/


    /**
     * Gets the body of the message.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Lazy-load: Build the body params and send them into their array
     *
     * @return array|null
     * @throws RuntimeException
     */
    protected function buildBodyParams()
    {
        if( empty($this->bodyParams) && !empty($this->body) )
        {

            $mediaType = $this->getMediaType();

            if( isset($this->bodyParsers[$mediaType]) )
            {
                // parse content of body

                $parsed = $this->bodyParsers[$mediaType]($this->getBody());

                if( !is_null($parsed) && !is_array($parsed) )
                {
                    throw new RuntimeException('Request body media type parser return value must be an array or null');
                }

                $this->bodyParams = $parsed;
            }
        }
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is application/x-www-form-urlencoded and the
     * request method is POST, this method MUST return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *                           These will typically be an array or object.
     */
    public function getBodyParams()
    {
        $this->buildBodyParams();

        return $this->bodyParams;
    }

    /**
     * Retrieve a parameter provided in the request body.
     * aka $_POST & body post
     * 
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function input( $key, $default = null )
    {
        $this->buildBodyParams();

        return isset($this->bodyParams[$key]) ? $this->bodyParams[$key] : $default;
    }

    /**
     * Register media type parser
     *
     * @param string|array   $mediaType A HTTP media type (excluding content-type params)
     * @param callable $callable  A callable that returns parsed contents for media type
     */
    public function registerMediaTypeParser( $mediaType, callable $callable )
    {
        if( $callable instanceof Closure )
        {
            $callable = $callable->bindTo($this);
        }

        if( !is_array($mediaType) )
        {
            $mediaType = [ $mediaType ]; // for multiple mimetype
        }

        // add to the body parsers :

        foreach( $mediaType as $type )
        {
            $this->bodyParsers[$type] = $callable;
        }
    }


    /*******************************************************************************
     * Parameters (e.g., POST and GET data)
     ******************************************************************************/


    /**
     * Fetch assocative array of body and query string parameters
     *
     * @return array
     */
    public function getParams()
    {
        $params = $this->getQueryParams();
        $postParams = $this->getBodyParams();

        if( $postParams )
        {
            $params = array_merge($params, (array)$postParams);
        }

        return $params;
    }


}
