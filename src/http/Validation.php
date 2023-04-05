<?php

namespace mrmoni\http;

/**
 * Request Handler
 * 
 * @author  Monirul Middya <monirulmiddya3@gmail.com>
 * @since 1.0.0
 * @todo    
 * 
 */

use Exception;
use ParseError;

class Validation
{
    /**
     * @var object CI_Controller
     */
    public $ci;

    function __construct()
    {
        // CI_Controller initialization
        $this->ci = &get_instance();

        $this->ci->load->library("form_validation");
    }

    // public function required_when_equal($str, $field)
    // {
    //     // return true;
    //     try {
    //         sscanf($field, '%[^.].%[^.]', $post_name, $value);
    //         pp($value);
    //         if ($_POST[$post_name] && ($_POST[$post_name] == $value)) {
    //             // exit("bcxn");
    //             return $str != "" ? true : false;
    //         } else {
    //             return true;
    //         }
    //     } catch (\Throwable | Exception | ParseError $e) {
    //         return false;
    //     }
    // }

    public function set_rules($array)
    {
        $this->ci->form_validation->set_rules($array);
        return $this->ci;
    }
}
