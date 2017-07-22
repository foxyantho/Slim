<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Http;

use Slim\Http\Interfaces\ResponseInterface;
use Slim\Http\Interfaces\HeadersInterface;

use InvalidArgumentException;
use RuntimeException;

/**
 * Response
 *
 * This class represents an HTTP response. It manages
 * the response status, headers, and body
 */
class Response implements ResponseInterface
{
    /**
     * Protocol version
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * Status code
     * @var int
     */
    protected $status = 200;

    /**
     * Reason phrase
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * Headers
     * @var \Slim\Interfaces\Http\HeadersInterface
     */
    protected $headers;

    /**
     * Body object
     * @var mixed
     */
    protected $body;

    /**
     * Status codes and reason phrases
     * @var array
     */
    protected $messages = [
        //Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        //Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    /**
     * Create new HTTP response.
     *
     * @param int               $status  The response status code
     * @param HeadersInterface  $headers The response headers
     */
    public function __construct( $status = 200, HeadersInterface $headers )
    {
        $this->status = $this->filterStatus($status);

        $this->headers = $headers;
    }


    /*******************************************************************************
     * Protocol
     ******************************************************************************/

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Set the specified HTTP protocol version.
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function protocolVersion( $version )
    {
        $this->protocolVersion = $version;

        return $this;
    }

    /*******************************************************************************
     * Status
     ******************************************************************************/

    /**
     * Gets the response status code.
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * Set the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function status( $code, $reasonPhrase = '' )
    {
        $code = $this->filterStatus($code);


        if( !is_string($reasonPhrase) )
        {
            throw new InvalidArgumentException('Reason phrase must be a string');
        }

        if( empty($reasonPhrase) && isset($this->messages[$code]) )
        {
            $reasonPhrase = $this->messages[$code]; // try default message if found
        }


        $this->status = $code; //todo: wrap into try/catch

        $this->reasonPhrase = $reasonPhrase;


        return $this;
    }

    /**
     * Filter HTTP status code.
     *
     * @param  int $status HTTP status code.
     * @return int
     * @throws \InvalidArgumentException If an invalid HTTP status code is provided.
     */
    protected function filterStatus( $code )
    {
        if( !is_integer($code) || $code < 100 || $code > 599 )
        {
            throw new InvalidArgumentException('Invalid HTTP status code');
        }

        return $code;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase()
    {
        if( $this->reasonPhrase )
        {
            return $this->reasonPhrase;
        }

        if( isset($this->messages[$this->status]) )
        {
            return $this->messages[$this->status];
        }

        return '';
    }


    /*******************************************************************************
     * Headers
     ******************************************************************************/


    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * Checks if a header exists by the given name.
     *
     * @param  string $name
     * @return bool
     */
    public function hasHeader( $name )
    {
        return $this->headers->has($name);
    }

    /**
     * Retrieves a header by the given name as an array of strings.
     *
     * @param  string   $name
     * @return string[] array of headers or an empty array
     */
    public function getHeader( $name )
    {
        return $this->headers->get($name, []);
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * header name as a string concatenated together using a comma.
     *
     * NOTE Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine( $name )
    {
        return implode(',', $this->headers->get($name, []));
    }

    /**
     * Set a header to add to the current response
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function header( $key, $value )
    {
        $this->headers->set($key, $value);

        return $this;
    }

    /**
     * Remove a header from the current response
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return self
     */
    public function withoutHeader( $name )
    {
        $this->headers->remove($name);

        return $this;
    }


    /*******************************************************************************
     * Body
     ******************************************************************************/


    /**
     * Write data to the response body.
     *
     * @param string $data
     * @return self
     */
    public function write( $content )
    {
        $this->body = $content;

        return $this;
    }

    /**
     * Gets body content of the message.
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get body lenght of current response
     * 
     * @return int
     */
    public function getBodyLength()
    {
        return strlen($this->body);
    }


    /*******************************************************************************
     * Response Helpers
     ******************************************************************************/


    /**
     * Redirect : prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param  string $url
     * @param  int    $status
     * @return self
     */
    public function redirect( $url, $status = 302 )
    {
        return $this->status($status)->header('location', (string) $url);
    }

    /**
     * Json : prepares the response object to return an HTTP Json
     * response to the client.
     *
     * @param  mixed  $data
     * @param  int    $status
     * @param  int    $encodingOptions encoding options
     * @return self
     */
    public function json( $data, $status = null, $encodingOptions = 0 )
    {
        $json = json_encode($data, $encodingOptions);

        // Ensure that the json encoding passed successfully

        if( $json === false )
        {
            throw new RuntimeException(json_last_error_msg(), json_last_error());
        }

        // add headers

        $this->header('content-type', 'application/json;charset=utf-8');

        $this->write($json);

        if( isset($status) )
        {
            $this->status($status);
        }

        return $this;
    }

    /**
     * Is this response empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->getStatusCode(), [204, 205, 304]);
    }

    /**
     * Is this response informational?
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Is this response OK?
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->getStatusCode() === 200;
    }

    /**
     * Is this response successful?
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Is this response a redirect?
     *
     * @return bool
     */
    public function isRedirect()
    {
        return in_array($this->getStatusCode(), [301, 302, 303, 307]);
    }

    /**
     * Is this response a redirection?
     *
     * @return bool
     */
    public function isRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Is this response forbidden?
     *
     * @return bool
     * @api
     */
    public function isForbidden()
    {
        return $this->getStatusCode() === 403;
    }

    /**
     * Is this response not Found?
     *
     * @return bool
     */
    public function isNotFound()
    {
        return $this->getStatusCode() === 404;
    }

    /**
     * Is this response a client error?
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Is this response a server error?
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }


    /**
     * Convert response to string.
     *
     * @return string
     */
    public function __toString()
    {
        $output = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );

        $output .= PHP_EOL;

        foreach( $this->getHeaders() as $name => $values )
        {
            $output .= sprintf('%s: %s', $name, $this->getHeaderLine($name)) . PHP_EOL;
        }

        $output .= PHP_EOL;

        $output .= (string) $this->getBody();

        return $output;
    }


}
