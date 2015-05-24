<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

namespace Slim\Http;

use Slim\Http\Interfaces\RequestInterface;
use Slim\Http\Interfaces\HeadersInterface;

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
     * Valid request methods
     * @var string[]
     */
    protected $validMethods = [
        'GET', 'POST', 'CONNECT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH', 'PUT', 'TRACE'
    ];


    /**
     * Create new HTTP request
     *
     * @param string            $method
     * @param HeadersInterface  $headers
     * @param mixed             $body
     */
    public function __construct( $method, HeadersInterface $headers, $body )
    {
        $this->method = $method;

        $this->headers = $headers;

        $this->body = $body;

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

        /*$this->registerMediaTypeParser('application/xml', function( $input ) {
            return simplexml_load_string($input);
        });*/

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
        if( !isset($this->method) )
        {
            if( $method = $this->getHeaderLine('X-Http-Method-Override') )
            {
                // override original method

                $this->method = $method;
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
        // @TODO always a string ?

        $method = strtoupper($method);

        if( !isset($this->validMethods[$method]) )
        {
            throw new InvalidArgumentException('Unsupported HTTP method : "' . $method . '"');
        }

        return $method;
    }

   /**
     * Does this request use a given method?
     *
     * @param  string $method HTTP method
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
     * Is this an AJAX request?
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }


    /*******************************************************************************
     * Headers
     ******************************************************************************/

    /**
     * Retrieves all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers.
     *               Each key MUST be a header name, and each value MUST be
     *               an array of strings.
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param  string $name Case-insensitive header field name.
     * @return bool         Returns true if any header names match the given header
     *                      name using a case-insensitive string comparison. Returns
     *                      false if no matching header name is found in the message.
     */
    public function hasHeader( $name )
    {
        return $this->headers->has($name);
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param  string   $name Case-insensitive header field name.
     * @return string[]       An array of string values as provided for the given
     *                        header. If the header does not appear in the message,
     *                        this method MUST return an empty array.
     */
    public function getHeader( $name )
    {
        return $this->headers->get($name, []);
    }

    /**
     * Retrieve a header by the given case-insensitive name, as a string.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader instead
     * and supply your own delimiter when concatenating.
     *
     * @param  string $name Case-insensitive header field name.
     * @return string
     */
    public function getHeaderLine( $name )
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * Get request content type
     * "application/json;charset=utf8;foo=bar" -> "application/json"
     *
     * @return string|null The request content type, if known
     */
    public function getContentType()
    {
        if( $contentType = $this->getHeader('Content-Type') )
        {
            $parts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($parts[0]);
        }

        return false;
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
        if( $result = $this->getHeader('Content-Length') )
        {
            return (int) $result;
        }

        return false;
    }

// @TODO cookies ?


    /*******************************************************************************
     * Query Params
     ******************************************************************************/


    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URL or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the composed URL or the `QUERY_STRING`
     * composed in the server params.
     *
     * @return array
     */
    public function getQueryParams()
    {
        if( $this->queryParams )
        {
            return $this->queryParams;
        }

        // parse query string 'x=x&y[]=y'

        $queryString = urldecode($this->getHeader('QUERY_STRING'));

        parse_str($queryString, $this->queryParams); // <-- URL decodes data

        return $this->queryParams;
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
        if( $this->bodyParams )
        {
            return $this->bodyParams;
        }

        if( !$this->body )
        {
            return;
        }

        $contentType = $this->getContentType();

        if( isset($this->bodyParsers[$contentType]) === true )
        {
            // parse content of body

            $parsed = $this->bodyParsers[$contentType]($this->getBody());

            if( !is_null($parsed) && !is_object($parsed) && !is_array($parsed) )
            {
                throw new RuntimeException('Request body media type parser return value must be an array, an object, or null');
            }

            $this->bodyParams = $parsed;
        }

        return $this->bodyParams;
    }

    /**
     * Register media type parser
     *
     * @param string   $mediaType A HTTP media type (excluding content-type params)
     * @param callable $callable  A callable that returns parsed contents for media type
     */
    public function registerMediaTypeParser( $mediaType, callable $callable )
    {
        $callable = $callable->bindTo($this);

        $this->bodyParsers[(string)$mediaType] = $callable;
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
