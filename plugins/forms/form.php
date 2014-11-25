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

require_once('formelement.php');
require_once('formvalidator.php');

class Form extends View {
	
	public static $count = 0;   // Static attribute assigns an ID to each form

	public $name;
        
	protected $formID;
	
	public $action = null;
	public $method = "POST";

	protected $elements = array();
        
	public function __construct($name,$view = "form/default") {
            // Set the name
            $this->name = $name;
            
            // Set up the view
            parent::__construct($view);
            
            // Assign an ID to this form
            $this->formID = Form::$count;
            Form::$count++;
	}
        
        /**
         * 
         * @param FormElement $formElement
         * 
         * Add an element to be rendered with the form
         */
        public function addElement(FormElement $formElement) {
            $this->elements[$formElement->name] = $formElement;
        }
        
        /**
         * 
         * @return array of elements
         * 
         * Get an array of all form elements
         */
        public function getElements() {
            return $this->elements;
        }
        
        /**
         * 
         * @param type $opFormat
         * @return html rendering of form
         * 
         */
        public function render($opFormat = 'html') {
            // Create the wrapper view
            $wrap = new View('form');
            
            // Assign the basic form metadata
            $wrap->assign('name',$this->name);
            $wrap->assign('method',$this->method);
            $wrap->assign('action',is_null($this->action) ? $_SERVER['REQUEST_URI'] : $this->action);
            $wrap->assign('form_id',$this->formID);
            
            // Next make sure the elements show their most up-to-date values
            foreach ($this->elements as $e) {
                $e->value($e->userInput());
            }
            $this->assign('elements',$this->elements);
            
            $wrap->assign('form',parent::render());
            
            
            return $wrap->render();
        }
        
        /**
         * 
         * @return boolean
         * 
         * Has this form been submitted?
         * This method will not work if the form submits to another page
         */
	public function submitted() {
            if ($_SERVER['REQUEST_METHOD'] == $this->method &&
                    ((isset($_POST['form_id']) && $_POST['form_id'] == $this->formID) || (isset($_GET['form_id']) && $_GET['form_id'] == $this->formID)) &&
                    !isset($_POST['cancel']) && 
                    !isset($_GET['cancel'])) {
                return true;
            }
            return false;
	}
	
	/**
         * 
         * @return array key/value pairs
         * 
         * Get all the data submitted through this form
         */
	public function getData() {
            $data = array();
            foreach ($this->elements as $e) {
                $data[$e->name] = $e->userInput();
            }
            return $data;
	}
    
        /**
         * 
         * @param type $array
         * 
         * Takes a set of key/value pairs and sets the form elements where possible
         */
	public function setData($array) {
            foreach ($this->elements as $element) {
                if (array_key_exists($element->__ToString(),$array)) {
                    $element->value($array[$element->name]);
                }
            }
	}
	
	/**
         * 
         * @return boolean
         * 
         * Validate the form submission by iterating through each element
         */
	public function validate() {
            $error = false;

            foreach ($this->elements as $element) {
                if ($element->validate() == false) {
                    $error = true;
                    break;
                }
            }

            if ($error) {
                return false;
            } else {
                return true;
            }
	}
	
}