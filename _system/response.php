<?php

/* 
 * Copyright (C) 2014 Clark Sirl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

abstract class System_Controller_Response {
    protected $data = array();
    
    protected $consoleText = "";
    
    /**
     * Render the response to the browser based on the HTTP request type
     */
    abstract public function render($opFormat = 'html');
    
    /**
     * Assign a key/value pair to this response
     */
    public function assign($name,$value) {
        $this->data[$name] = $value;
    }
    
    /**
     * Send text to the "console" (i.e. anything only to be shown in DEBUG mode)
     * How this text is used is up to the response type
     */
    public function setConsole($string) {
        $this->consoleText = $string;
    }
}

/** 
 * AjaxResponse object - Controller methods can return this as a means of passing
 * data back to the browser without requiring an HTML view.
 * Note that AjaxResponse::render() will result in a blank page for
 * all output formats other than ajax.
 * For ajax calls, AjaxResponse objects will be rendered in the browser as
 * a json-encoded array of key/value pairs
 */
class AjaxResponse extends System_Controller_Response {
    public function render($opFormat = 'html') {
        if ($opFormat == 'ajax') {
            echo json_encode($this->data);
            
            return true;
        }
        return false;
    }
}