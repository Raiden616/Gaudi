<?php
class HTML {
    protected $id;
    protected $attributes = array();
    protected $classes = array();
    protected $prepended;
    protected $appended;

    public function attr($key,$value) {
            switch($key) {
                    case "id":
                            $this->id = $value;
                            break;
                    default:
                            $this->attributes[$key] = $value;
            }
    }

    public function addClass($value) {
            /* Use this method to add a class so that it does not conflict with the default class */
            $this->classes[] = $value;
    }

    public function prependHTML($string) {
        $this->prepended .= $string;
    }
	
    public function appendHTML($string) {
            $this->appended .= $string;
    }
	
}
?>