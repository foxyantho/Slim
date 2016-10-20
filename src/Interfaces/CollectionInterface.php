<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Interfaces;

use ArrayAccess;
use Countable;
use IteratorAggregate;


interface CollectionInterface extends ArrayAccess, Countable, IteratorAggregate
{

    function normalizeKey( $key );


    function has( $key );

    function get( $key );

    function set( $key, $value );

    function remove( $key );


    function all();

    function add( array $data );

    function clear();


}
