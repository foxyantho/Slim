<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */

namespace Slim\Http;

use Slim\Http\Interfaces\ResponseInterface;
use Slim\Http\Interfaces\HeadersInterface;

use InvalidArgumentException;

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
     *
     * @var array
     */
    protected static $messages = [
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
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
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
    ];

    /**
     * Create new HTTP response
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
     * Get HTTP protocol version
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
     * @param  string $version HTTP protocol version
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
     * Gets the response Status-Code.
     * The Status-Code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * If no Reason-Phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * Status-Code.
     *
     * @link  http://tools.ietf.org/html/rfc7231#section-6
     * @link  http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param integer     $code         The 3-digit integer result code to set.
     * @param null|string $reasonPhrase The reason phrase to use with the
     *                                  provided status code; if none is provided, implementations MAY
     *                                  use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function status( $code )
    {
        $this->status = $this->filterStatus($code);

        return $this;
    }

    /**
     * Filter HTTP status code
     *
     * @param  int $status HTTP status code
     * @return int
     * @throws \InvalidArgumentException If invalid HTTP status code
     */
    protected function filterStatus( $code )
    {
        if( !is_integer($code) || !isset(static::$messages[$code]) )
        {
            throw new InvalidArgumentException('Invalid HTTP status code');
        }

        return $code;
    }

    /**
     * Gets the response Reason-Phrase, a short textual description of the Status-Code.
     *
     * Because a Reason-Phrase is not a required element in a response
     * Status-Line, the Reason-Phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * Status-Code.
     *
     * @link   http://tools.ietf.org/html/rfc7231#section-6
     * @link   http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string|null Reason phrase, or null if unknown.
     */
    public function getReasonPhrase()
    {
        return isset(static::$messages[$this->status]) ? static::$messages[$this->status] : null;
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
     * @return array Returns an associative array of the message's headers. Each
     *               key MUST be a header name, and each value MUST be an array of strings.
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
     * @return string[]
     */
    public function getHeader( $name )
    {
        return $this->headers->get($name, []);
    }

    /**
     * Retrieve a header by the given name, as a string.
     *
     * This method returns all of the header values of the given
     * header name as a string concatenated together using a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader instead
     * and supply your own delimiter when concatenating.
     *
     * @param  string $name Case-insensitive header name.
     * @return string
     */
    public function getHeaderLine( $name )
    {
        return implode(',', $this->headers->get($name, []));
    }  

    /**
     * Set a header to add to the current response
     *
     * @param  string          $header Header name
     * @param  string|string[] $value  Header value(s).
     * @return self
     */
    public function header( $key, $value )
    {
        $this->headers->set($key, $value);

        return $this;
    }

    /**
     * Remove a header from the current response
     *
     * @param  string $header HTTP header to remove
     * @return self
     */
    public function withoutHeader( $header )
    {
        $this->headers->remove($header);

        return $this;
    }


    /*******************************************************************************
     * Body
     ******************************************************************************/


    /**
     * Write data to the response body
     *
     * @param string $data
     * @return self
     */
    public function body( $content )
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

    /**
     * Finalize: prepares this response and returns an array
     * of [status, headers, body]. This array is passed to outer middleware
     * if available or directly to the Slim run method.
     *
     * @return array[int status, array headers, string body]
     */
    public function finalize()
    {
        // Prepare response
        if( in_array($this->status, [204, 304]) ) // @TODO use isempty() ?
        {
            $this->withoutHeader('Content-Type');
            $this->withoutHeader('Content-Length');

            $this->body('');
        }
        else
        {
            $this->header('Content-Length', $this->getLength());
        }

        return [
            $this->getStatusCode(),
            $this->getHeaders(),
            $this->getBody()
        ];
    }

    /*******************************************************************************
     * Response Helpers
     ******************************************************************************/


    /**
     * Redirect
     *
     * This method prepares the response object to return an HTTP Redirect response
     * to the client.
     *
     * @param  string $url    The redirect destination
     * @param  int    $status The redirect HTTP status code
     * @return self
     */
    public function redirect( $url, $status = 302 )
    {
        return $this->status($status)->header('Location', $url);
    }

    /**
     * Is this response empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->getStatusCode(), [201, 204, 304]);
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
     * Convert response to string
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

        $output .= $this->getBody();

        return $output;
    }


}
