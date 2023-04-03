<?php

namespace mrmoni\CI_Rest;

use mrmoni\http\Request;
use mrmoni\http\Response;

/**
 * Restful API Controller
 * 
 * 
 * @author  Monirul Middya <monirulmiddya3@gmail.com>
 * @version 1.0.0
 * @link    https://github.com/mrmoni/ci-rest
 * @see     https://github.com/mrmoni/ci-rest/blob/master/examples/Example.php
 * 
 */

class CI_Rest extends \CI_Controller
{

    /**
     * @var object mrmoni\http\Request;
     */
    protected $request;

    /**
     * @var object mrmoni\http\Response;
     */
    protected $response;

    /**
     * response format 
     * 
     * @var string mrmoni\http\Response - format
     */
    protected $format;

    /**
     * Body Format - flag
     * 
     * @var bool Default $bodyFormat for json()
     */
    protected $bodyFormat = false;
    
    function __construct() 
    {
        parent::__construct();
        
        // Request initialization
        $this->request = new Request;
        // Response initialization
        $this->response = new Response;

        // Response setting
        if ($this->format) {
		    $this->response->setFormat($this->format);
        }
    }

    /**
     * Output by JSON format with optinal body format
     * 
     * @deprecated 1.3.0
     * @param array|mixed Callback data body, false will remove body key
     * @param bool Enable body format
     * @param int HTTP Status Code
     * @param string Callback message
     * @return string Response body data
     * 
     * @example
     *  json(false, true, 401, 'Login Required', 'Unauthorized');
     */
    protected function json($data=[], $bodyFormat=null, $statusCode=null, $message=null)
    {
        // Check default Body Format setting if not assigning
        $bodyFormat = ($bodyFormat!==null) ? $bodyFormat : $this->bodyFormat;
        
        if ($bodyFormat) {
            // Pack data
            $data = $this->_format($statusCode, $message, $data);
        } else {
            // JSON standard of RFC4627
            $data = is_array($data) ? $data : [$data];
        }

        return $this->response->json($data, $statusCode);
    }

    /**
     * Format Response Data
     * 
     * @deprecated 1.3.0
     * @param int Callback status code
     * @param string Callback status text
     * @param array|mixed|bool Callback data body, false will remove body key 
     * @return array Formated array data
     */
    protected function _format($statusCode=null, $message=null, $body=false)
    {
        $format = [];
        // Status Code field is necessary
        $format['code'] = ($statusCode) 
            ?: $this->response->getStatusCode();
        // Message field
        if ($message) {
            $format['message'] = $message;
        }
        // Body field
        if ($body !== false) {
            $format['data'] = $body;
        }
        
        return $format;
    }

    /**
     * Pack array data into body format
     * 
     * You could override this method for your application standard
     * 
     * @param array|mixed $data Original data
     * @param int HTTP Status Code
     * @param string Callback message
     * @return array Packed data
     * @example
     *  $packedData = pack(['bar'=>'foo], 401, 'Login Required');
     */
    protected function pack($data, $statusCode=200, $message=null)
    {
        $packBody = [];

        // Status Code
        if ($statusCode) {
            
            $packBody['code'] = $statusCode;
        }
        // Message
        if ($message) {
            
            $packBody['message'] = $message;
        }
        // Data
        if (is_array($data) || is_string($data)) {
            
            $packBody['data'] = $data;
        }
        
        return $packBody;
    }

    /**
     * Default Action
     */
    protected function _defaultAction()
    {
        /* Response sample code */
        // $response->data = ['foo'=>'bar'];
		// $response->setStatusCode(401);
        
        // Codeigniter 404 Error Handling
        show_404();
    }

    /**
     * Set behavior to a action before route
     *
     * @param String $action
     * @param Callable $function
     * @return boolean Result
     */
    protected function _setBehavior($action, Callable $function)
    {
        if (array_key_exists($action, $this->behaviors)) {

            $this->behaviors[$action] = $function;
            return true;
        }

        return false;
    }

    /**
     * Action processor for route
     * 
     * @param array Elements contains method for first and params for others 
     */
    private function _action($params)
    {
        // Shift and get the method
        $method = array_shift($params);

        // Behavior
        if ($this->behaviors[$method]) {
            $this->behaviors[$method]();
        }

        if (!isset($this->routes[$method])) {
            $this->_defaultAction();
        }

        // Get corresponding method name
        $method = $this->routes[$method];

        if (!method_exists($this, $method)) {
            $this->_defaultAction();
        }

        return call_user_func_array([$this, $method], $params);
    }
}