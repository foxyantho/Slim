<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
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


}
