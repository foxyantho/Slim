<?php
/**
 * Slim
 *
 * A simple PHP framework for PHP 5.3.0 or newer
 *
 * @author		Josh Lockhart <info@joshlockhart.com>
 * @link		http://slim.joshlockhart.com
 * @copyright	2010 Josh Lockhart
 * 
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Slim View
 *
 * The View is delegated the responsibility of rendering a template. Usually
 * you will subclass View and, in the subclass, re-implement the render
 * method to use a custom templating engine, such as Smarty, Twig, Markdown, etc.
 *
 * It is very important that the View *echo* the final template output. DO NOT
 * return the output... if you return the output rather than echoing it, the
 * Slim Response body will be empty.
 *
 * @author Josh Lockhart <info@joshlockhart.com>
 * @since Version 1.0
 */
class View {
	
	/**
	 * @var Response
	 */
	protected $response;
	
	/**
	 * @var array Data passed from a Route callback function [ $key => $value, ... ]
	 */
	protected $data;
	
	/**
	 * Constructor
	 *
	 * @param Response $response The HTTP response object
	 */
	final public function __construct( Response $response ) {
		$this->response = $response;
		$this->data = array();
	}
	
	/**
	 * Set and/or get View data
	 *
	 * @param array $data An array of key => value pairs
	 * @return array
	 */
	final public function data( $data = null ) {
		if( is_array($data) ) {
			$this->data = $data;
		}
		return $this->data;
	}
	
	/**
	 * Render template
	 *
	 * This method is responsible for rendering the a template using
	 * data provided by the Route callback function. The final
	 * template output should be echo()'d out, NOT returned.
	 *
	 * I strongly recommend that you override this method in a subclass if
	 * you need more advanced templating (ie. Twig or Smarty).
	 *
	 * This default view assumes there is a "templates" directory in the same
	 * directory as your bootstrap.php file.
	 *
	 * @param string $template Name of a template, as specified in Slim::render()
	 * @return void
	 */
	public function render( $template ) {
		extract($this->data);
		include( Slim::root() . 'templates' . DIRECTORY_SEPARATOR . $template );
	}
	
}

?>