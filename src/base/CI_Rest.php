<?php

namespace mrmoni\base;

use mrmoni\http\Request;
use mrmoni\http\Response;
use mrmoni\http\Validation;

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
     * @var object mrmoni\http\Validation;
     */
    protected $validation;

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

    function __construct()
    {
        parent::__construct();

        // Request initialization
        $this->request = new Request;
        // Response initialization
        $this->response = new Response;
        // Validation initialization
        $this->validation = new Validation;

        // Response setting
        if ($this->format) {
            $this->response->setFormat($this->format);
        }
    }
}
