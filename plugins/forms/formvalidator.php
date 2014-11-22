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

/**
 * Archetype of FormValidator class type.
 * 
 * FormValidators can extend this class to act as validator methods for
 * FormElements.
 * 
 * To use a FormValidator to validate your forms, instantiate the FormValidator
 * of your choosing and assign it to a FormElement with FormElement::addValidator(FormValidator).
 */
abstract class FormValidator {
    protected $error = "This is not a valid entry";
    
    /**
     * 
     * @param type $value
     * @return boolean
     * 
     * Validate a value against this validator.
     * Returns true if the value passed is valid, false if not.
     * All validators must implement this method
     */
    abstract public function validate($value);
    
    public function error() {   return $this->error;    }
}

/**
 * FormValidator to simply checking if the passed value is empty.
 */
class FormValidator_Required extends FormValidator {
    protected $error = "Required";
    
    public function validate($value) {
        return !empty($value);
    }
}