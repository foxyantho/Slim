<?php

namespace Slim\Http\Interfaces;


interface ResponseInterface
{

    function getProtocolVersion();

    function protocolVersion( $version );


    function getStatusCode();

    function status( $code, $reasonPhrase = '' );

    function getReasonPhrase();


    function getHeaders();

    function hasHeader( $name );

    function getHeader( $name );

    function getHeaderLine( $name );

    function header( $key, $value );

    function withoutHeader( $name );


    function write( $content );

    function getBody();

    function getBodyLength();


    function redirect( $url, $status = 302 );

    function json( $data, $status = 200, $encodingOptions = 0 );


    function isEmpty();

    function isInformational();

    function isOk();

    function isSuccessful();

    function isRedirect();

    function isRedirection();

    function isForbidden();

    function isNotFound();

    function isClientError();

    function isServerError();


}
