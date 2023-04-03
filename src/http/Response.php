<?php

namespace mrmoni\http;

use Exception;

/**
 * Response Handeler with formatting based on CI_Output
 * 
 * @author  Monirul Middya <monirulmiddya3@gmail.com>
 * @since 1.0.0
 * 
 */

class Response
{

    /**
     * Allows child classes to override the
     * status code that is used in their API.
     *
     * @var array<string, int>
     */
    protected $codes = [
        'created'                   => 201,
        'deleted'                   => 200,
        'updated'                   => 200,
        'no_content'                => 204,
        'invalid_request'           => 400,
        'unsupported_response_type' => 400,
        'invalid_scope'             => 400,
        'temporarily_unavailable'   => 400,
        'invalid_grant'             => 400,
        'invalid_credentials'       => 400,
        'invalid_refresh'           => 400,
        'no_data'                   => 400,
        'invalid_data'              => 400,
        'access_denied'             => 401,
        'unauthorized'              => 401,
        'invalid_client'            => 401,
        'forbidden'                 => 403,
        'resource_not_found'        => 404,
        'not_acceptable'            => 406,
        'resource_exists'           => 409,
        'conflict'                  => 409,
        'resource_gone'             => 410,
        'payload_too_large'         => 413,
        'unsupported_media_type'    => 415,
        'too_many_requests'         => 429,
        'server_error'              => 500,
        'unsupported_grant_type'    => 501,
        'not_implemented'           => 501,
    ];

    /**
     * @var string HTTP response formats
     */
    const FORMAT_RAW = 'raw';
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';
    const FORMAT_JSONP = 'jsonp';
    const FORMAT_XML = 'xml';
    /**
     * @var object CI_Controller
     */
    public $ci;
    /**
     * @var array the formatters that are supported by default
     */
    public $contentTypes = [
        self::FORMAT_RAW => 'text/plain;',
        self::FORMAT_HTML => 'text/html;',
        self::FORMAT_JSON => 'application/json;', // RFC 4627
        self::FORMAT_JSONP => 'application/javascript;', // RFC 4329
        self::FORMAT_XML => 'application/xml;', // RFC 2376
    ];
    /**
     * @var string the response format. This determines how to convert [[data]] into [[content]]
     * when the latter is not set. The value of this property must be one of the keys declared in the [[formatters]] array.
     * By default, the following formats are supported:
     *
     * - [[FORMAT_RAW]]: the data will be treated as the response content without any conversion.
     *   No extra HTTP header will be added.
     * - [[FORMAT_HTML]]: the data will be treated as the response content without any conversion.
     *   The "Content-Type" header will set as "text/html".
     * - [[FORMAT_JSON]]: the data will be converted into JSON format, and the "Content-Type"
     *   header will be set as "application/json".
     * - [[FORMAT_JSONP]]: the data will be converted into JSONP format, and the "Content-Type"
     *   header will be set as "text/javascript". Note that in this case `$data` must be an array
     *   with "data" and "callback" elements. The former refers to the actual data to be sent,
     *   while the latter refers to the name of the JavaScript callback.
     * - [[FORMAT_XML]]: the data will be converted into XML format. Please refer to [[XmlResponseFormatter]]
     *   for more details.
     *
     * You may customize the formatting process or support additional formats by configuring [[formatters]].
     * @see formatters
     */
    private $_format = self::FORMAT_JSON;
    /**
     * @var int the HTTP status code to send with the response.
     */
    private $_statusCode = 200;

    function __construct()
    {
        // CI_Controller initialization
        $this->ci = &get_instance();
    }

    /**
     * Set Response Format into CI_Output
     * 
     * @param string Response format
     */
    public function setFormat($format)
    {
        $this->_format = $format;
        // Use formatter content type if exists
        if (isset($this->contentTypes[$this->_format])) {
            $this->ci->output
                ->set_content_type($this->contentTypes[$this->_format]);
        }

        return $this;
    }

    /**
     * Set Response Data into CI_Output
     * 
     * @todo    Format data before send
     * @param mixed Response data
     * @return object self
     */
    public function setData($data)
    {
        // Format data
        $data = $this->format($data, $this->_format);
        // CI Output
        $this->ci->output->set_output($data);

        return $this;
    }

    /**
     * Get Response Body from CI_Output
     * 
     * @return string Response body data
     */
    public function getOutput()
    {
        // CI Output
        return $this->ci->output->get_output();
    }

    /**
     * @return int the HTTP status code to send with the response.
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    /**
     * Sets the response status code.
     * This method will set the corresponding status text if `$text` is null.
     * @param int $code the status code
     * @param string $text HTTP status text base on PHP http_response_code().
     * @throws Exception if the status code is invalid.
     * @return $this the response object itself
     */
    public function setStatusCode($code, $text = null)
    {
        if ($code === null) {
            $code = 200;
        }
        // Save code into property
        $this->_statusCode = (int) $code;
        // Check status code
        if ($this->getIsInvalid()) {
            throw new Exception("The HTTP status code is invalid: " . $this->_statusCode);
        }
        // Set HTTP status code with options
        if ($text) {
            // Set into CI_Output
            $this->ci->output->set_status_header($this->_statusCode, $text);
        } else {
            // Use PHP function with more code support
            http_response_code($this->_statusCode);
        }

        return $this;
    }

    /**
     * @return bool whether this response has a valid [[statusCode]].
     */
    public function getIsInvalid()
    {
        return $this->getStatusCode() < 100 || $this->getStatusCode() >= 600;
    }

    /**
     * Sends the response to the client.
     */
    public function send()
    {
        $this->ci->output->_display();
        exit;
    }

    /**
     * Common format funciton by format types. {FORMAT}Format()
     * 
     * @param array  Pre-handle array data
     * @param string Format
     * @return string Formatted data by specified formatter
     */
    public function format($data, $format)
    {
        // Case handing. ex. json => Json
        $format = ucfirst(strtolower($format));
        $formatFunc = "format" . $format;
        // Use formatter if exists
        if (method_exists($this, $formatFunc)) {

            $data = $this->{$formatFunc}($data);
        } elseif (is_array($data)) {
            // Use JSON while the Formatter not found and the data is array
            $data = $this->formatJson($data);
        }

        return $data;
    }

    /**
     * Common format funciton by format types. {FORMAT}Format()
     * 
     * @param array Pre-handle array data
     * @return string Formatted data
     */
    public static function formatJson($data)
    {
        return json_encode($data);
    }

    /**
     * Response formate
     *
     * @param array|string|null $data
     *
     * @return array
     */
    protected function respondFormat(int $status = null, string $message = '', $data = null)
    {
        return [
            "code" => $status,
            "message" => $message,
            "data" => $data
        ];
    }

    /**
     * JSON output shortcut
     * 
     * @param array|mixed Callback data body, false will remove body key
     * @param int Callback status code
     * @return string Response body data
     */
    public function json($data, $statusCode = null)
    {
        // Set Status Code
        if ($statusCode) {
            $this->setStatusCode($statusCode);
        }

        $this->setFormat(Response::FORMAT_JSON);

        if (!is_null($data)) {
            $this->setData($data);
        }

        return $this->send();
    }

    /**
     * json response create
     * 
     * @param int HTTP Status Code
     * @param string message
     * @param array|mixed $data
     * @return string formated data
     * 
     */
    public function create($statusCode = 200, $message = "", $data = null)
    {
        return $this->json($this->respondFormat($statusCode, $message, $data), $statusCode);
    }


    /**
     * Used when there is a server error.
     *
     * @param string      $message The error message to show the user.
     * @param int|null $code
     *
     * @return string
     */
    public function serverError(string $message = 'Internal Server Error',int $code = null)
    {
        return $this->json($this->respondFormat($code == null ? $this->codes['server_error'] : $code, $message), $this->codes['server_error']);
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * PSR-7 standard
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return self
     */
    public function withAddedHeader($name, $value)
    {
        $this->ci->output->set_header("{$name}: {$value}");

        return $this;
    }
}