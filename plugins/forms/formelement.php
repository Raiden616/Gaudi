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

abstract class FormElement extends View {
    public $name;
    public $type;

    protected $label = "";

    protected $note;
    protected $value;
    protected $placeholder;

    protected $required;
    protected $errors = array();
    protected $validators = array();
    
    public function __construct($name,$view = 'formelement/input') {
        $this->name = htmlentities($name);
        
        // Do the view
        parent::__construct($view);
    }
    
    /**
     * 
     * @param type $string
     * 
     * Set the label for the form element
     */
    public function label($string) {
        $this->label = htmlentities($string);
    }
    
    /**
     * 
     * @param type $string
     * 
     * Set the value of this form element
     */
    public function value($string) {
        $this->value = htmlentities($string);
    }
    
    /**
     * 
     * @param FormValidator $v
     * 
     * Assign a validator to this form element
     */
    public function addValidator(FormValidator $v) {
        $this->validators[] = $v;
    }
    
    /**
     * 
     * @param type $bool
     * 
     * Set this form as required by adding the appropriate validator
     */
    public function required($bool = true) {
        $this->addValidator(new FormValidator_Required());
    }
    
    /**
     * 
     * @return string
     * 
     * Get the value of this element, either the set value or a user-submitted one
     */
    public function userInput($method = "post") {
        $sent = $method == "post" ? $_POST : $_GET;
        if (isset($sent[$this->name])) {    // If a value for this item has been posted through...
            // ... prepare and use it.
            $str = $sent[$this->name];
            $str = htmlspecialchars($str);
            $str = utf8_encode($str);
            //$str = utf8_decode($str);
            return $str;				
        }
        // Otherwise use whatever we have set as the value
        return $this->value;
    }
    
    /**
     * 
     * @return boolean
     * 
     * Does the value we have for this form element meet this element's validation spec?
     */
    public function validate() {
        $error = false;
        foreach ($this->validators as $v) {
            if (!$v->validate($this->userInput())) {    // If a validator doesn't validate, log the error
                $this->errors[] = $v->error();
                $error = true;
            }
        }
        
        return !$error;
    }
    
    public function render($opFormat = 'html') {
        $this->assign('name',$this->name);
        $this->assign('value',$this->value);
        $this->assign('label',$this->label);
        
        return parent::render($opFormat);
    }
}

/*
 *	Form elements
 */

class Input extends FormElement {
    protected $placeholder = "";
    
    /**
     * 
     * @param type $string
     * 
     * Set the placeholder for the form element
     */
    public function placeholder($string) {
        $this->placeholder = htmlentities($string);
    }
    
    public function render($opFormat = 'html') {
        $this->assign('placeholder',$this->placeholder);
        return parent::render($opFormat);
    }
}

class TextArea extends Input {
    function __construct($name,$view = "formelement/textarea") {
        parent::__construct($name,$view);
    }
}

class Submit extends FormElement {
    function __construct($name,$view = "formelement/submit") {
        parent::__construct($name,$view);
        
        $this->label = "Submit";    // Default button text
    }
}

class Select extends FormElement {
    protected $options = array();
    
    function __construct($name,$view = "formelement/select") {
        parent::__construct($name,$view);
    }
    
    public function options(array $options) {
        foreach ($options as $k => $v) {
            if (is_array($v)) {
                $v = reset($v);
            }
            
            $this->addOption($v,$k);
        }
    }
    
    /**
     * 
     * @param type $value
     * @param type $key (optional)
     * @return type
     * 
     * Add an option to this select element.
     * Returns the key of the newly inserted element
     */
    public function addOption($value,$key = null) {
        if (is_null($key)) {
            $this->options[] = htmlentities($value);
            return count($this->options)-1;
        } else {
            $this->options[htmlentities($key)] = htmlentities($value);
            return $key;
        }
    }
    
    /**
     * 
     * @param type $oldKey
     * @param type $newKey
     * 
     * Alter the key value of an existing array element
     */
    public function setKey($oldKey,$newKey) {
        if (isset($this->options[htmlentities($oldKey)])) {
            $this->options[htmlentities($newKey)] = $this->options[htmlentities($oldKey)];
            unset($this->options[htmlentities($oldKey)]);
        }
    }
    
    public function render($opFormat = 'html') {
        $this->assign('options',$this->options);
        return parent::render($opFormat);
    }
}

/*

class TextArea extends FormElement {
	public $type = "textarea";
	
	public function parse() {
		$buffer = "";
		

			$buffer .= '<textarea id="'.$this->id.'" name="'.$this->name.'" ';
			if (!empty($this->classes)) {
				$buffer .= 'class="';
				foreach ($this->classes as $v) {
					$buffer .= "$v ";
				}
				$buffer .= '" ';
			}
			foreach ($this->attributes as $attribute) {
				$attr = array_search($attribute,$this->attributes);
				$buffer .= $attr.'="'.$attribute.'" ';
			}
			if (!empty($this->placeholder)) {
				$buffer .= 'placeholder="'.$this->placeholder.'" ';
			}
			$buffer .= '>';
			if (!empty($this->value)) {
				$buffer .= $this->value;
			}		
			$buffer .= '</textarea>';
		
		return $buffer;
	}
}

class WYSIWYG extends TextArea {
	public function __construct($name,$id = null,$value = null) {
		parent::__construct($name,$id,$value);
		
		$this->type = "wysiwyg";
		$this->addClass("wysiwyg");
	}
	
	public function parse() {
		$buffer = "";
		
			$buffer .= '<textarea id="'.$this->id.'" name="'.$this->name.'" ';
			if (!empty($this->classes)) {
				$buffer .= 'class="';
				foreach ($this->classes as $v) {
					$buffer .= "$v ";
				}
				$buffer .= '" ';
			}
			foreach ($this->attributes as $attribute) {
				$attr = array_search($attribute,$this->attributes);
				$buffer .= $attr.'="'.$attribute.'" ';
			}
			if (!empty($this->placeholder)) {
				$buffer .= 'placeholder="'.$this->placeholder.'" ';
			}
			$buffer .= '>';
			if (!empty($this->value)) {
				$buffer .= $this->value;
			}		
			$buffer .= '</textarea>';
		
		return $buffer;
	}
	
	public function userInput() {
		if (isset($_POST[$this->__ToString()])) {
			$html = $_POST[$this->__ToString()];
			// Use the HTMLPurifier library to clean the input
			$purifier = new HTMLPurifier();
			$cleanHTML = $purifier->purify($html);
			return $cleanHTML;
		}
	}
}

// Page editor element

class PageEditor extends WYSIWYG {
        public function __construct($name,$id = null,$value = null) {
                parent::__construct($name,$id,$value);

                $this->type = "wysiwyg";
                $this->addClass("pageeditor");
        }
}

class Select extends FormElement {
	public $type = "select";
    private $options = array();
	private $defaultText = "Select...";
	
	public function setdefaulttext($value = null) {
		if (!is_null($value)) {
			$this->defaultText = $value;
		}
	}
	
	public function parse() {
		$buffer = "";
		
			$buffer .= '<select id="'.$this->id.'" ';
			if (!empty($this->classes)) {
				$buffer .= 'class="';
				foreach ($this->classes as $v) {
					$buffer .= "$v ";
				}
				$buffer .= '" ';
			}
			foreach ($this->attributes as $attribute) {
				$attr = array_search($attribute,$this->attributes);
				$buffer .= $attr.'="'.$attribute.'" ';
			}
			$buffer .= 'name="'.$this->name.'">';
			
	        if (empty($this->value)) { $buffer .= '<option value="">'.$this->defaultText.'</option>'; }
	        foreach ($this->options as $option) {
				$value = $option;
				if (is_array($value) && isset($value['value'])) {
					$value = $value['value'];
				}
				
	            $buffer .= '<option value="'.$value.'"';
	            if (!empty($this->value) && $this->value == $value) {
	                $buffer .= ' selected';
	            }
	            $buffer .='>'.(is_array($option) && isset($option['label']) && !empty($option['label']) ? $option['label'] : $value).'</option>';
	        }
	        $buffer .= '</select>';
		
		return $buffer;
	}
    
    public function addOptions(array $options) {
        $this->options = $options;
    }
}

class Radio extends FormElement {
    public $type = "radio";
    private $options = array();
	
	public function __construct($name,$id = null,$value = null) {
		parent::__construct($name,$id,$value);
		$this->addClass('checkbox_contain');
	}
    
    public function parse() {
    	$buffer = "";
		

			$buffer .= '<div ';
				if (!empty($this->classes)) {
					$buffer .= 'class="';
					foreach ($this->classes as $v) {
						$buffer .= "$v ";
					}
					$buffer .= '" ';
				}
				$buffer .='>';
			foreach ($this->options as $option) {
			    $buffer .= '<input type="radio" name="'.$this->name.'" value="'.$option['value'].'"';
				$buffer .= isset($this->value) && $this->value == $option['value'] ? "checked" : "";
				$buffer .= ' />';
			    $buffer .= '<span class="checkboxlabel">'.(isset($option['label']) && !empty($option['label']) ? htmlentities($option['label']) : htmlentities($option['value'])).'</span><div class="clear"></div>';
			}
			$buffer .= '</div>';
        
        return $buffer;
    }
    
    public function addOptions(array $options) {
        $this->options = $options;
    }
}

class ColourPicker extends Input {
	public function __construct($name,$id = null,$value = null) {
		parent::__construct($name,$id,$value);
		$this->addClass('minicolorsTrigger');
	}
}

class DateInput extends Input {
	public $type = "date";
	
	public function __construct($name,$id = null,$value = null) {
		parent::__construct($name,$id,$value);
		$this->addClass("datepicker");
	}
}

class DateTimeInput extends Input {
	
	public $type = "datetime";
	protected $value = array("date" => "","time" => "");
	
	public function __construct($name,$id = null,$value = null) {
		parent::__construct($name,$id,$value);
	}
	
	public function parse() {
		$buffer = "";

			$buffer .= '<div class="';
			foreach ($this->classes as $class) {
				$buffer .= "$class ";
			}
			$buffer .= '" ';
				foreach ($this->attributes as $attribute) {
					$attr = array_search($attribute,$this->attributes);
					$buffer .= $attr.'="'.$attribute.'" ';
				}
			$buffer .= ' id="{$this->id}">';
			
			$buffer .= '<input type="text" class="datepicker" style="width:150px;" name="'.$this->name.'[date]" value="'.$this->value['date'].'">';
			$buffer .= '<input type="text" class="timepicker" style="width:100px;" name="'.$this->name.'[time]" value="'.$this->value['time'].'">';
			
			$buffer .= '</div>';
		
		return $buffer;
	}

	public function value($time) {
		$time = strtotime($datetime);
		
		$date = date('Y-m-d',strtotime($time));
		$time = date('H:i:s',strtotime($time));
		
		if(!isset($_POST[$this->name]['date'])) {
			$this->value['date'] = $date;
		}
		if(!isset($_POST[$this->name]['time'])) {
			$this->value['time'] = $time;
		}
	}
	
	public function userInput() {
		// Returns date & time formatted for SQL
		if (empty($_POST[$this->name])) {
			return "";
		} else {
			$date = $_POST[$this->name]['date'];
			$time = $_POST[$this->name]['date'];
			
			$date = new DateTime("$date $time");
			return $date->format('Y-m-d H:i:s');
		}
	}
}

class CheckList extends FormElement {
    public $type = "checkbox";
    private $options = array();
	
	public function __construct($name,$id = null,$value = null) {
		parent::__construct($name,$id,$value);
		$this->addClass('checkbox_contain');
	}
    
    public function parse() {
    	$buffer = "";
		

			$buffer .= '<div ';
				if (!empty($this->classes)) {
					$buffer .= 'class="';
					foreach ($this->classes as $v) {
						$buffer .= "$v ";
					}
					$buffer .= '" ';
				}
				$buffer .='>';
			foreach ($this->options as $option) {
			    $buffer .= '<input type="checkbox" name="'.$this->name.'[]" value="'.$option['value'].'" />';
			    $buffer .= '<span class="checkboxlabel">'.$option['value'].'</span><div class="clear"></div>';
			}
			$buffer .= '</div>';
        
        return $buffer;
    }
    
    public function addOptions(array $options) {
        $this->options = $options;
    }
	
	public function userInput() {
		return isset($_POST[$this->__ToString()]);
	}
}

class CheckBox extends FormElement {
	public $type = "checkbox";
	
	public function parse() {
		$buffer ="";

			$buffer .= '<input type="checkbox"';
			if ($this->getvalue()) {
				$buffer .= ' checked ';
			}
			if (!empty($this->classes)) {
				$buffer .= 'class="';
				foreach ($this->classes as $v) {
					$buffer .= "$v ";
				}
				$buffer .= '" ';
			}
			foreach ($this->attributes as $attribute) {
					$attr = array_search($attribute,$this->attributes);
					$buffer .= $attr.'="'.$attribute.'" ';
				}
			$buffer .= 'name="'.$this->name.'" id="'.$this->id.'"/>';

		
		return $buffer;
	}
	
	public function userInput() {
		return isset($_POST[$this->__ToString()]);
	}
}

class ListInput extends FormElement {
	public $type = "listinput";
	
	private $fields = array(
							""
						);	// Default to a single field with no key
	private $requiredNumber = 0;
	
	public function required($requiredNumber = 1) {
		parent::required();
		$this->requiredNumber = $requiredNumber;
	}
	
	public function addField($key,$label) {
		if ($this->fields == array("")) {
				unset($this->fields);
				$this->fields = array();
		}
		$this->fields[$key] = $label;
	}
	
	public function validate() {
		$error = false;
		
		if ($this->required) {
			$count = 0;
			foreach ($_POST[$this->name] as $k => $v) {
				if (!empty($v)) {
					$count++;
				}
			}
			if ($count < $this->requiredNumber) {
				array_push($this->errors,"Too few values");
				$error = true;
			}
		}
        
        foreach ($this->validators as $validator) {
			$value = (isset($_POST[$this->name]) ? $_POST[$this->name] : null);
            if (!$validator->validate($value)) {
                array_push($this->errors,$validator->error());
                $error = true;
            }
        }
		
		if ($error) {
			$this->addClass('error');
			return false;
		} else {
			return true;
		}
	}
	
	// Overriding function to get back the user input
	public function userInput() {
		$sent = $this->method == "POST" ? $_POST : $_GET;
		if (isset($sent[$this->__ToString()])) {
			$data = $sent[$this->__ToString()];
			
			$userInput = array();
			$i = 0;
			$endOfRows = false;
			while (!$endOfRows) {
				$row = array();
				foreach ($this->fields as $k => $v) {
					if (!isset($data[$k][$i])) {	// No field value for this row, so we must be at the end of the rows
						$endOfRows = true;
						break;
					}
					$row[$k] = $data[$k][$i];
				}
				
				if (!$endOfRows) {
					// Save the row
					$userInput[] = $row;
					// Reset for the next row
					unset($row);
					$row = array();
					$i++;	// Increment the i for the next row
				}
			}
			
			foreach ($userInput as $k => $v) {
				$notEmpty = false;
				foreach ($v as $w) {
					if (!empty($w)) {
						$notEmpty = true;
						break;
					}
				}
				if (!$notEmpty) {
					unset($userInput[$k]);
				}
			}
			
			return $userInput;
		}
		return $this->value;
	}
	
	public function parse() {
		// Canibalise the userInput() method to make sure the value is in the right format, as it will not POST correctly in by default
		$this->value = $this->userInput();
		
		$buffer = "<div class=\"formelement-listinput\">";
		
		
		
		
		$buffer .= "<ul class=\"formelement-listinput-list\">";
		
		// Header row
		// Only show the headers if there is more than one column
		if (count($this->fields) > 1) {
			$buffer .= "<li class=\"formelement-listinput-header\">";
			foreach ($this->fields as $k => $v) {
				$buffer .= "<span class=\"formelement-listinput-field\">";
				$buffer .= htmlentities($v);
				$buffer .= "</span>";
			}
			$buffer .= "</li>";
		}
		
		foreach ($this->value as $k => $v) {
			$k = htmlentities($k);
			$v = is_array($v) ? $v : array($v);
			
			$buffer .= "<li class=\"formelement-listinput-datarow\">";
			
			foreach ($this->fields as $l => $w) {
				$l = htmlentities($l);
				$value = "";
				if (array_key_exists($l,$v)) {
					$value = $v[$l];
				} else if (count($v) == 1 && array_key_exists("",$v)) { // Failsafe
					$value = reset($v);
				}
				$value = htmlentities($value);
			
				$buffer .= "<span class=\"formelement-listinput-field\">";
				$buffer .= "<span class=\"formelement-listinput-field-value\">$value</span>";
				$buffer .= "<input type=\"text\" name=\"{$this->name}[{$l}][]\" value=\"$value\"/>";
				$buffer .= "</span>";
			
			}
			
			// Row links
			$buffer .= "<span><a href=\"#\" class=\"formelement-listinput-link-edit\">Edit...</a>".
						"<a href=\"#\" class=\"formelement-listinput-link-delete\">Delete</a>&nbsp;<a href=\"#\" class=\"formelement-listinput-link-editDone\">Save</a></span>";
			
			$buffer .= "</li>";

		}
		
		// Add a hidden dummy row to copy
		$buffer .= "<li class=\"formelement-listinput-dummyrow\">";
		foreach ($this->fields as $l => $w) {
			$l = htmlentities($l);
			$buffer .= "<span class=\"formelement-listinput-field\"><span class=\"formelement-listinput-field-value\"></span><input type=\"text\" name=\"{$this->name}[{$l}][]\"/></span>";
		}
		// Row links
		$buffer .= "<span><a href=\"#\" class=\"formelement-listinput-link-edit\">Edit...</a><a href=\"#\" class=\"formelement-listinput-link-delete\">Delete</a>&nbsp;<a href=\"#\" class=\"formelement-listinput-link-editDone\">Save</a></span>";
		$buffer .= "</li>";
		
		$buffer .= "</ul>";
		
		// Total links
		
		$buffer .= "<div class=\"formelement-listinput-links\">
						<a class=\"formelement-listinput-link-add\" href=\"#\">Add&nbsp;+</a></div>";
		
		
		
		$buffer .= "</div>";
		
		return $buffer;
	}
}

class Submit extends FormElement {
	public $type = "submit";
	
	function __construct($name,$id = null,$value=null) {
		parent::__construct($name,$id = null,$value=null);
		$this->addClass('button');
	}
	
	public function parse() {
		$buffer = "";

			$buffer .= '<input type="submit" value="';
			if (!empty($this->value)) {
			    $buffer .= $this->value;
			} else {
			    $buffer .= "Submit";
			}
			$buffer .='"';
			if (!empty($this->classes)) {
				$buffer .= 'class="';
				foreach ($this->classes as $v) {
					$buffer .= "$v ";
				}
				$buffer .= '" ';
			}
			$buffer .= 'id="'.$this->id.'" name="'.$this->name.'" ';
			foreach ($this->attributes as $attribute) {
				$attr = array_search($attribute,$this->attributes);
				$buffer .= $attr.'="'.$attribute.'" ';
			}
			$buffer .= '/>';

		return $buffer;
	}
}

class Cancel extends FormElement {
	public $type = "cancel";
	
	public function __construct($id = null,$value=null) {
		$this->id = $id;
		
		if (!is_null($value)) {
			$this->value = $value;
		}
		$this->name = "cancel";
		
		$this->addClass('button');
		$this->addClass('cs-cancel');
	}
	
	public function parse() {
		$buffer = "";


			$buffer = "<input type=\"submit\" value=\"";
			if (!empty($this->value)) {
				$buffer .= $this->value;
			} else {
				$buffer .= "Cancel";
			}
			$buffer .= "\" id=\"".$this->id."\" name=\"{$this->name}\" ";
			if (!empty($this->classes)) {
				$buffer .= 'class="';
				foreach ($this->classes as $v) {
					$buffer .= "$v ";
				}
				$buffer .= '" ';
			}
			foreach ($this->attributes as $attribute) {
				$attr = array_search($attribute,$this->attributes);
				$buffer .= $attr."=\"".$attribtue."\" ";
			}
			$buffer .= "/>";

		return $buffer;
	}
}

class Button extends FormElement {
	public $type = "button";

	public function parse() {
		$buffer = '<button';
		foreach ($this->attributes as $attribute) {
			$attr = array_search($attribute,$this->attributes);
			$buffer .= ' '.$attr.'="'.$attribute.'" ';
		}
		$buffer .= '>';
		$buffer .= $this->value;
		$buffer .= '</button>';

		return $buffer;
	}
}

class Hidden extends FormElement {
	public $type = "hidden";
	
	public function parse() {
		$buffer = '<input type="hidden" id="'.$this->id.'" name="'.$this->name.'" ';
		if (!empty($this->value)) {
			$buffer .= 'value="'.$this->value.'" ';
		}
		$buffer .= '/>';
		
		return $buffer;
	}
}

class Password extends FormElement {
	public $type = "password";
	
	public function parse() {
		$buffer = "";

			$buffer .= '<input type="password" ';
			if (!empty($this->classes)) {
				$buffer .= 'class="';
				foreach ($this->classes as $v) {
					$buffer .= "$v ";
				}
				$buffer .= '" ';
			}
			$buffer .= 'id="'.$this->id.'" name="'.$this->name.'"';
			if (!empty($this->value)) {
				$buffer .= ' value="'.$this->value.'" ';
			}
			if (!empty($this->placeholder)) {
				$buffer .= 'placeholder="'.$this->placeholder.'" ';
			}
			$buffer .= '/>';
	
		return $buffer;
	}
}

class ContactForm extends Form {
	public function setDefault() {
		// Set the method
		$this->method = "POST";
		// Create name element
		$e = new Input('name');
		$e->label('Your Name');
		$e->required();
		$this->addelement($e);
		
		// Create "Your Email" element
		$e = new Input('email');
		$e->label("Your Email");
		$e->addvalidator(new EmailValidator());
		$this->addelement($e);
		
		// Create message body field
		$e = new TextArea("body");
		$e->label("Message");
		$e->required();
		$this->addelement($e);

		// Add submit button field
		$e = new Submit('submit');
		$e->value("Send");
		$this->addelement($e);
		
		return $this;
	}
	
	public function sendEmail($toEmail = null) {
		if (Form::POST()) {
			if (is_null($toEmail)) {
				$toEmail = WEBMASTER_EMAIL;
			}
			$data = $this->getData();
			
			$to      = $toEmail;
			$subject = "New Contact Form Submission from ".SITENAME;
			$message = $data['body'];
			
			// Create email object
			$email = new Email();
			$email->addToAddress($toEmail);
			$email->subject($subject);
				$html = new Template("emails/contact");
				$html->assign('from',(!empty($data['email']) ? "{$data['name']} ({$data['email']})" : $data['name']));
				$html->assign('body',$message);
				$html->assign('subject',$subject);				
			$email->contentFromView($html);
			
			return $email->send();
		}
	}
	
	private function error() {
		$page = new Page();
		
		$page->header('title',"Error");
		$page->header('description',"There has been an error sending your email");
		
		$page->assign('heading',"Error");
		$page->assign('content',"<p>An error has occured and your email has not been sent. Apologies for the inconvenience; please try again later.</p>");
		
		$page->render();
	}
}
?>

*/