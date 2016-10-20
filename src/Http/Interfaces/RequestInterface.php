<?php

namespace Slim\Http\Interfaces;


interface RequestInterface
{

    function getProtocolVersion();


    function getMethod();

    function isMethod( $method );


    function isGet();

    function isPost();

    function isPut();

    function isPatch();

    function isDelete();

    function isHead();

    function isOptions();

    function isXhr();


    function getHeaders();

    function hasHeader( $name );

    function getHeader( $name );


    function getContentType();

    function getMediaType();

    function getContentTypeParams();

    function getContentLength();


    function getUriAuthority();

    function getUriBasePath();

    function getUriPath();

    function getUriRoot();


    function getServerParams();


    function getAttributes();

    function getAttribute( $name, $default = false );

    function attribute( $name, $value );

    function attributes( array $attributes );

    function withoutAttribute( $name );


    function getQueryParams();

    function query( $key, $default = null );

    function getBody();

    function getBodyParams();

    function input( $key, $default = null );

    function registerMediaTypeParser( $mediaType, callable $callable );

    function getParams();


}
