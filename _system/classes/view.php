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

class View extends System_Controller_Response {
	protected $content;
	
	protected $file = false;
	
	protected $data = array();
	
	function __construct($name) {
            /*
             * // CSTEMP - Temporarily removed database integration as need to figure that out again
             * 
            parent::__construct($this->view_dbTable,$this->view_idField);
            
            $bRead = $this->read($name);
            
            if ($bRead && !Router::isCMS()) {
                // View exists in database
                $this->content = $this[$this->contentField];
            } else {
                */
                if (!$this->setfile($name)) {   // Not got from file
                    // We CANNOT use the default Error:: methods here 
                    // because they rely on being able to generate the default view.
                    // If it can't find that then something is VERY wrong and we
                    // will just be looping forever.
                    // So just echo out and kill execution NOW.
                    $error = "Unable to locate view \"$name\" in either database or views directory";
                    return Error::_sysError($error);
                }
           /* } */
	}
	
	/**
	 * Assigns values to any PHP variables used in the view
	 */
	/*public function assign($name,$value) {
		$this->data[$name] = $value;
	}
         * 
         */
	
	/**
	 * Set the content to a file source
	 * File must be located in "views" folder
	 */
	public function setfile($file) {
            // Check main views directory
            $file = "views/$file.php";
            if (file_exists($file) && is_readable($file)) {
			$this->content = $file;
			$this->file = true;
			return true;
            }
            // Not found in main views directory
            
            // Check each plugin views directory
            foreach(Sys_Plugins::viewLocations() as $dir) {
                $file = "$dir/$file";
                if (file_exists($file) && is_readable($file)) {
                    $this->content = $file;
                    $this->file = true;
                    return true;
                }
            }
            
            // Not found
            return false;
	}
        
        /**
         * Get this view as an array
         */
        public function getArray() {
            // First render view to get HTML contents
            $content = $this->render('html');
            
            // If we have included a view as a value in this view,
            // then recursively get each as an array
            $retData = $this->data;
            foreach ($retData as $k => $v) {
                if ($retData[$k] instanceof View) {
                    $retData[$k] = $retData[$k]->getArray();
                }
            }
            
            return array(
                'content' => $content,
                'data' => $retData
            );
        }
	
	/**
	 * Echos out the finished view
	 */
	public function render($opFormat = 'html') {
            switch ($opFormat) {
                case 'ajax':
                    // Ajax: Encrypt view as json array
                    $json = json_encode($this->getArray());
                    return $json;
                    break;
                case 'html':
                default:
                    ob_start();
                    extract($this->data,EXTR_OVERWRITE);
                    include ($this->content);
                    $buffer = ob_get_contents();
                    ob_end_clean();
                    return $buffer;
                    break;
                /*
		if ($this->file) {
			extract($this->data,EXTR_OVERWRITE);    // Extract data so that the content can use assigned variables
			include($this->content);    // Include the file specified to hold the content
		} else {
			echo $this->content;
		}
                 */
            }
	}

	public function __ToString() {
		ob_start();
		$this->render();
		$buffer = ob_get_contents();
		ob_end_clean();

		return $buffer;
	}
}

/**
 * Construct with a $name that matches a database page name, then it will instantiate the database row as a page.
 * If $name is a file, then it will use view (db page takes priority)
 * If no $name specified, blank page accepting "$content" and "$heading" variables will be made
 * 
 * Also accepts second parameter of "$wrapper", which works the same way but with page layouts
 */
class Page extends View {
	protected $header = array(
                            'title' => META_TITLE,
                            'description' => META_DESC,
                            'keywords' => META_KEYW,
                            'bodyclass' => ''
                    );
	protected $pageCSS = "";
	protected $pageJS = "";
	
	//private $id = null;
	private $name = null;
	
        private $defaultWrapperName = "index";
	public $wrapper = null;
	private $theme = null;

	function __construct($name = 'default',$wrapper = null) {
            if (is_null($wrapper)) {    $wrapper = $this->defaultWrapperName;   }
            parent::__construct($name);
            
            /*
            
            // Sort out wrapper
            if (!Router::isCMS()) { // We do not use database wrappers in CMS.
                $bGotTheme = false;
                $theme = null;
                if (!empty($this['theme'])) {
                        //$this->theme = $row['theme']; // Set theme

                        $theme = new Model('gaudi_css');
                        $bGotTheme = $theme->read($this['theme']);

                } else {
                        // If file page, or no associated theme, use default theme if exists
                        $theme = new Model('gaudi_css');
                        $bGotTheme = $theme->read(1,'active');
                }
                //if (!empty($theme)) {
                if ($bGotTheme) {
                    $this->theme = $theme;
                    $wrapper = $this->theme['phpFileSrc'];
                }			
            }
             */

            $this->wrapper = new Wrapper($wrapper);
	}
	
	public function header($header,$content) {
		// Used to add headers/metas to the site wrapper e.g. title, description
		$this->header[$header] = $content;
	}
	
	public function setCSS($content) {
		$this->pageCSS = $content;
	}
	
	public function setJS($content) {
		$this->pageJS = $content;
	}
        
        public function getArray() {
            // First render view to get HTML contents
            $content = parent::render('html');
            
            // If we have included a view as a value in this view,
            // then recursively get each as an array
            $retData = $this->data;
            foreach ($retData as $k => $v) {
                if ($retData[$k] instanceof View) {
                    $retData[$k] = $retData[$k]->getArray();
                } else if ($k == 'content') {   // This is content
                    // The "content" key in view data is reserved,
                    // and thus not parsed out
                    unset($retData[$k]);
                }
            }
            
            $array['content'] = $content;
            $array['data'] = $retData;
            $array['title'] = $this->header['title'];
            $array['description'] = $this->header['description'];
            $array['keywords'] = $this->header['keywords'];
            $array['bodyclass'] = $this->header['bodyclass'];
            
            return $array;
        }
        
        public function assignToWrapper($name,$value) {
            $this->wrapper->assign($name,$value);
        }
	
	public function render($opformat = 'html') {
            switch ($opformat) {
                case 'ajax':
                    echo json_encode($this->getArray());
					
                    break;
                case 'html':
                default:    // HTML output (default)
                    
                    // If wrapper failed to initialise, just render this as a view
                    if (!$this->wrapper->isInitialised()) {
                        return parent::render();
                    }
                    
                    // Wrapper is initialised, so we:
                    //  - Assign any required data to wrapper
                    //  - Assign this view as main content of wrapper
                    //  - Render wrapper
                    
                    if (!empty($this->pageCSS)) {
                            $this->wrapper->assign('pageCSS','<link rel="stylesheet" type="text/css" href="/'.ROUTE.'/resources/page.css" />');					
                    }
                    if (!empty($this->pageJS)) {
                            $this->wrapper->assign('pageJS','<script type="text/javascript" language="javascript" src="/'.ROUTE.'/resources/page.js"></script>');					
                    }
					
                    if (!empty($this->theme) && !is_null($this->theme)) {
                            $this->wrapper->assign('themeCSS','<link rel="stylesheet" type="text/css" href="/css/theme/'.$this->theme->id.'.css" />');
                    } else {
                            $this->wrapper->assign('themeCSS','<link rel="stylesheet" type="text/css" href="/css/theme.css" />');
                    }
/*
                    if ($TopNavIsSetUp=1) {	// TODO
                            $wrapper->assign('topNavigation',new TopNavigation());
                    }
 * 
 */

                    if (DEBUG == 'true') {
                        $this->wrapper->assign('consoleText',$this->consoleText);
                    }
                    
                    $this->wrapper->assign('content',parent::render());

                    if (!Router::isCMS()) {
                            // Get any relevant layout parts
                            $part = new Model('gaudi_themelayoutpartviews',array('theme','layoutPart'));
                            $part->addJoin(new Model('gaudi_layoutparts'),'layoutPart','layoutPart');
                            while($part->read(
                                        array($this->wrapper,is_null($this->theme) ? 0 : $this->theme->id),
                                        array('layout','theme')
                                    )) {
						
                                    $v = $part['view'];
                                    if (!empty($part['fileView']) && $part['file']) {
                                            $filePath = SERROOT."/views/".$part['fileView'].".php";
                                            if (file_exists($filePath) && is_readable($filePath)) {
                                                    $v = $part['fileView'];
                                            }
                                    }
                                    $v = new View($v);
                                    $this->wrapper->assign($part['var_name'],$v);
                            }
                    }

                    $this->wrapper->header($this->header);

                    return $this->wrapper->render();

            }
		
	}
	
	/* Static functions */
	
	public static function getAllPages() {
            $return = array();
            
            while ($this->read()) {
                array_push($return,clone $this);
            }
            
	}
	
	public static function fetchPage($name) {
		$page = new Model('gaudi_pages');
		if ($page->read($name,'name')) {
			self::updateView($page->id);
			return $page;
		}
		return false;
	}
	
	public static function fetchCSS($route) {
            $page = new Model('gaudi_pages');
            if ($page->read($route,is_numeric($route) ? 'id' : 'name')) {
                return $page['css'];
            } else {
                return "";
            }
	}
	
	public static function fetchJS($route) {
		$model = new Model('gaudi_pages',is_numeric($route) ? 'id' : 'name');
		$model->read($route);
		return $model['javascript'];
		/*
		$qry = "SELECT `javascript` FROM `pages` ";
		if (is_numeric($route)) {
			$qry .= "WHERE `id` = '%u' ";
		} else {
			$qry .= "WHERE `name` = '%s' ";
		}
		$qry .= "LIMIT 0,1";
		$qry = new MYSQLQuery($qry,$route);
		$row = $qry->fetch();
		return $row['javascript'];
		*/
	}
	
	public static function getPageDetails($id) {
            $page = new Page();
            $user = new Model('gaudi_users');
            $page->addJoin($user,'createdBy');
            
            $row = array();
            
            if ($page->read($id)) {
                $row['name'] = $page['name'];
                $row['title'] = $page['title'];
                $row['description'] = $page['description'];
                $row['content'] = $page['content'];
                $row['css'] = $page['css'];
                $row['javascript'] = $page['javascript'];
                $row['lastmodified'] = $page['lastmodified'];
                $row['views'] = $page['views'];
                $row['created'] = $page['created'];
                $row['theme'] = $page['theme'];
                
                $row['createdBy'] = $user['email'];
                
                return $row;
            }
            
            return false;
	}
	
        // Increment the historical view count on a page
	private static function updateView($id,$inc = 1) {
            $page = new Page();
            if ($page->read($id)) {
                $this['views'] = $page['views']+1;
                $page->save();
            }
	}
	
	public static function deletePage($id) {
            $page = new Page();
            if ($page->read($id)) {
                return $page->delete();
            }
	}
}

class Wrapper extends View {
	private $header = array();
	private $initialised = false;
        
	function __construct($fileName) { // Must be file
		if (!$this->setfile("wrappers/$fileName")) {
                    Error::log("Error using wrapper \"$fileName\", rendering raw content.");
                    // Failed to generate wrapper
                    $this->initialised = false;
		} else {
                    $this->initialised = true;
                }
	}
        
        public function isInitialised() {   return $this->initialised;  }
	
	public function header($header) {
		$this->header = $header;
	}
	
	public function render($opformat = 'html') {
		ob_start();
		
		extract($this->header,EXTR_OVERWRITE);
		extract($this->data,EXTR_OVERWRITE);
		
		require_once(SERROOT.'/_system/views/top.php'); // Include HTML top tags
		echo parent::render();
		require_once(SERROOT.'/_system/views/bottom.php'); // Include HTML bottom tags
		
		$buffer = ob_get_contents();
		ob_end_clean();
                
		return $buffer;
	}
}

class IFrame {
	private $href;
	
	private $height = null;
	private $width = null;
	
	function __construct($href) {
		$this->href = $href;
	}
	
	public function width($val = null) {
		if (is_null($val)) {
			return $href;
		}
		$this->width = intval($val);
	}
	
	public function height($val = null) {
		if (is_null($val)) {
			return $href;
		}
		$this->height = intval($val);
	}
	
	public function href($val = null) {
		if (is_null($val)) {
			return $href;
		}
		$this->href = $val;
	}
	
	private function parse() {
		$buffer = "<iframe style=\"overflow:hidden;\" frameborder=\"0\"";
		
		$buffer .= "src=\"{$this->href}\" ";
		if (!is_null($this->height) && is_numeric($this->height)) {
			$buffer .= "height=\"{$this->height}\" ";
		}
		if (!is_null($this->width) && is_numeric($this->width)) {
			$buffer .= "width=\"{$this->width}\" ";
		}
		
		$buffer .= "></iframe>";
		
		return $buffer;
	}
	
	public function __ToString() {
		$this->render();
	}
	
	public function render() {
		$buffer = $this->parse();
		
		echo $buffer;
	}
}

?>
