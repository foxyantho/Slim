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
 * This class represents an HTTP response. It manages the response status, headers, and body
 */
class Request implements RequestInterface
{

    /**
     * server environment variables at the time the request was created.
     * @var array
     */
    protected $serverParams;

    /**
     * The request protocol version
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * The request method GET, PATCH
     * @var string
     */
    protected $method;

    /**
     * Valid request methods
     * @var string[]
     */
    protected $validMethods = [
        'GET', 'POST', 'CONNECT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH', 'PUT', 'TRACE'
    ];


    /**
     * Uri scheme http https
     * @var string
     */
    protected $uriScheme;

    /**
     * Uri Host "localhost"
     * @var string
     */
    protected $uriHost;

    /**
     * Uri path ; without query string "/page/welcome"
     * @var string
     */
    protected $uriPath;


    /**
     * Uri query string "page=hello&lang=fr"
     * @var string
     */
    protected $queryString;

    /**
     * The parsed query params
     * @var array
     */
    protected $queryParams;

    /**
     * check if the request query string has been parsed into a array
     * @var bool
     */
    protected $isQueryParsed = false;


    /**
     * The request headers
     * @var \Slim\Http\Interfaces\HeadersInterface
     */
    protected $headers;


    /**
     * The request body content
     * @var string
     */
    protected $body;

    /**
     * check if the request body has been parsed (if possible) into a array or object
     * @var bool
     */
    protected $isBodyParsed = false;

    /**
     * The parsed body params
     * @var array
     */
    protected $bodyParams;

    /**
     * List of request body parsers (e.g., url-encoded, JSON, XML, multipart)
     * @var array
     */
    protected $bodyParsers = [];

    /**
     * check if the request body upload files has been parsed into a array
     * @var bool
     */
    protected $isUploadedFilesParsed = false;

    /**
     * List of uploaded files
     * @var array
     */
    protected $uploadedFiles;

    /**
     * Media type ex: "application/json"
     * @var string|null
     */
    protected $mediaType;

    /**
     * Undocumented variable ex: ['charset' => 'utf8', 'foo' => 'bar']
     * @var array
     */
    protected $mediaTypeParams;


    /**
     * Create new HTTP request.
     *
     * @param string                $method
     * @param HeadersInterface      $headers
     * @param EnvironmentInterface  $serverParams
     * @param mixed                 $body
     */
    public function __construct( $method, HeadersInterface $headers, EnvironmentInterface $server, $body )
    {
        $this->serverParams = $server;

        // method

        $this->method = $this->filterMethod($method); // @FIXME:wrap exception ; handle exception if not know

        // URI part

        $isSecure = ( isset($server['https']) && $server['https'] === 'on' );

        $scheme = $isSecure ? 'https' : 'http';

        $this->uriScheme = $scheme;

        // URI authority: Host

        $host = $server['http_host'] ?: $server['server_name'];

        $this->uriHost = $host;

        // request URI, stripped from query params

        $requestUri = rawurldecode($server['request_uri']); // &20

        if( ($pos = strpos($requestUri, '?')) !== false )
        {
            $requestUri = substr($requestUri, 0, $pos);
        }

        $this->uriPath = '/' . ltrim($requestUri, '/');

        //$requestUri = parse_url('http://example.com'.$serverParams['request_uri'], PHP_URL_PATH);

        // query string

        $queryString = $server['query_string'];

        $this->queryString = $queryString; // todo if empty ?

        // protocol version
        
        if( isset($server['server_protocol']) )
        {
            $this->protocolVersion = str_replace('HTTP/', '', $server['server_protocol']);
        }

        // headers ; request content
        
        $this->headers = $headers;

        $this->body = $body;


        // body params parsers :

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function( $input )
        {
            parse_str(urldecode($input), $result); // if not argment#2 -> extract()

            return $result;
        });

        $this->registerMediaTypeParser('application/json', function( $input )
        {
            $result = json_decode($input, true); // as array

            return ( is_array($result) ? $result : null );
        });

        $this->registerMediaTypeParser('application/xml', function( $input )
        {
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);

            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);

            return ( $result !== false ? $result : null );
        });

        $this->registerMediaTypeParser('text/xml', function( $input )
        {
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);

            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
    
            return ( $result !== false ? $result : null );
        });



        // createRequestFromFactory($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
        // @TODO enctype="multipart/form-data" for upload form
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
        // $customMethod = $this->getHeader('x-http-method-override');

        return $this->method;
    }

    /**
     * Validate the HTTP method
     *
     * @param  null|string $method
     * @return null|string
     * @throws \InvalidArgumentException on invalid HTTP method.
     */
    protected function filterMethod( $method ) // todo
    {
        if( !is_string($method) )
        {
            throw new InvalidArgumentException(sprintf('Unsupported HTTP method ; must be a string, received %s',
                ( is_object($method) ? get_class($method) : gettype($method) )
            ));
        }

        $method = strtoupper($method);

        if( !in_array($method, $this->validMethods) )
        {
            throw new InvalidArgumentException(sprintf('Unsupported HTTP method "%s" provided',
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
        return $this->getHeader('x-requested-with') === 'XMLHttpRequest';
    }



    /*******************************************************************************
     * Uri
     ******************************************************************************/

     /**
      * Return the URi scheme "http", "https"
      *
      * @return string
      */
     public function getUriScheme()
     {
         return $this->uriScheme;
     }

    /**
     * Retrieve the authority portion of the URI.
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included. "user:pass@site.com:port"
     *
     * @return string
     */
    public function getUriAuthority()
    {
        return $this->uriHost;
    }

    /**
     * Get the Uri path "/page/welcome" or "/"
     *
     * @return string
     */
    public function getUriPath()
    {
        return $this->uriPath;
    }

    /**
     * Get website's root uri ( http://example.com/ )
     * 
     * @return string
     */
    public function getUriRoot()
    {
        return $this->getUriScheme().'://'.$this->getUriAuthority().'/'; // getUriSubFolder
    }



    /*******************************************************************************
     * Query Params ; (e.g., GET data)
     ******************************************************************************/


    /**
     * Get query string of the request "page=welcome&lang=fr"
     *
     * @return string
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * Retrieve query string arguments : the deserialized query string arguments, if any.
     * aka $_GET
     *
     * @return array
     */
    public function getQueryParams()
    {
        if( !$this->isQueryParsed )
        {
            // lazy

            $this->queryParams = $this->parseQueryString($this->queryString);

            $this->isQueryParsed = true;
        }

        return $this->queryParams;
    }

    /**
     * Retrieve a query parameter provided in the request body. aka $_GET
     * 
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function query( $key, $default = null )
    {
        if( !$this->isQueryParsed )
        {
            // lazy

            $this->queryParams = $this->parseQueryString($this->queryString);

            $this->isQueryParsed = true;
        }

        return isset($this->queryParams[$key]) ? $this->queryParams[$key] : $default;
    }

    /**
     * Parse a query string into an array
     *
     * @param string $queryString
     * @return array
     */
    protected function parseQueryString( $queryString )
    {
        $parsed = [];

        parse_str($queryString, $parsed);

        return $parsed;
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
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param  string   $name Case-insensitive header field name.
     * @return string|null
     */
    public function getHeader( $key )
    {
        return $this->headers->get($key);
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param  string $name Case-insensitive header field name.
     * @return bool
     */
    public function hasHeader( $key )
    {
        return $this->headers->has($key);
    }


    /**
     * Get request content type
     * "application/json;charset=utf8;foo=bar"
     *
     * @return string|false The request content type, if known
     */
    public function getContentType()
    {
        return $this->getHeader('content-type');
    }

    /**
     * Get request media type, if known.
     * "application/json;charset=utf8;foo=bar" -> "application/json"
     *
     * @return string|null The request media type, minus content-type params
     */
    public function getMediaType()
    {
        if( !isset($this->mediaType) )
        {
            // lazy

            if( $contentType = $this->getContentType() )
            {
                $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

                $mediaType = strtolower($contentTypeParts[0]);
            }

            $this->mediaType = isset($mediaType) ? $mediaType : null;
        }

        return $this->mediaType;
    }

    /**
     * Get request content type media params, if known
     * "application/json;charset=utf8;foo=bar" -> ['charset' => 'utf8', 'foo' => 'bar']
     *
     * @return array
     */
    public function getMediaTypeParams()
    {
        if( !isset($this->mediaTypeParams) )
        {
            // lazy
        
            $contentTypeParams = [];

            if( $contentType = $this->getContentType() )
            {
                $parts = preg_split('/\s*[;,]\s*/', $contentType);

                foreach( array_slice($parts, 1) as $part ) // first is content-type : not needed
                {
                    $conf = explode('=', $part);

                    $contentTypeParams[strtolower($conf[0])] = $conf[1];
                }
            }

            $this->mediaTypeParams = $contentTypeParams;
        }

        return $this->mediaTypeParams;
    }

    /**
     * Get request content length, if known
     *
     * @return int|null
     */
    public function getContentLength()
    {
        if( $result = $this->getHeader('content-length') )
        {
            return (int) $result;
        }

        return null;
    }


    /*******************************************************************************
     * Body ; (e.g., POST data)
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
     * Build the body params and send them into their array
     *
     * @return array|null
     * @throws RuntimeException
     */
    protected function parseBodyParams()
    {
        $parsed = null;

        if( isset($this->body) )
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
            }
        }

        return $parsed;
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
        if( !$this->isBodyParsed )
        {
            $this->bodyParams = $this->parseBodyParams();

            $this->isBodyParsed = true;
        }

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
        if( !$this->isBodyParsed )
        {
            $this->bodyParams = $this->parseBodyParams();

            $this->isBodyParsed = true;
        }

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
        // add multiple mimetype

        $mediaType = is_array($mediaType) ? $mediaType : [$mediaType]; 

        // add to the body parsers :

        foreach( $mediaType as $type )
        {
            $this->bodyParsers[$type] = $callable;
        }
    }


    /*******************************************************************************
     * File Params
     ******************************************************************************/

    /**
     * Retrieve normalized file upload data.
     * These values MAY be prepared from $_FILES or the message body during instantiation
     *
     * @return array 
     */
    public function getUploadedFiles()
    {
        if( !$this->isUploadedFilesParsed )
        {
            // lazy

            $this->uploadedFiles = isset($_FILES) ? $this->parseUploadedFiles($_FILES) : [];

            $this->isUploadedFilesParsed = true;
        }

        return $this->uploadedFiles;
    }

    /**
     * Parse a non-normalized, i.e. $_FILES superglobal, tree of uploaded file data.
     * Always return an array of uploaded files, even if a single file, in case of multiple
     *
     * @param array $uploadedFiles
     * @return array
     */
    protected function parseUploadedFiles( array $uploadedFiles )
    {
        $parsed = [];

        // $fieldName : 'form_input' ; $fieldValues : [ 'name' => '', 'type' => '' ] || [ 'name' => [ 0,1 => '' ], 'type' => [ 0,1 => ''] ]

        foreach( $uploadedFiles as $fieldName => $fieldValues )
        {
            // $attrName : 'name' ; $attrValues : 'name' || [ 'name' => [ 0,1 => '' ] ]

            foreach( $fieldValues as $attrName => $attrValues ) 
            {
                $attrValues = is_array($attrValues) ? $attrValues : [$attrValues]; // if multiple upload

                // $fileNum : 0,1 ; $attrValue : 'filename.xxx'

                foreach( $attrValues as $fileNum => $attrValue )
                {
                    $parsed[$fieldName][$fileNum][$attrName] = $attrValue;

                    // ['name', 'type', 'tmp_name', 'error', 'size']
                }
            }

        }

        return $parsed;
    }


}
