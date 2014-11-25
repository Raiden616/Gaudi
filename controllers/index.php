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

class Controller_Index {
    public static function index() {
        
        // Create the page object
        $page = new Page();
        
        // Set the content of the page
        $page->assign('heading',"Gaudi Test Page");
        
        
        $form = new Form("testForm");
        
        $e = new Input('input');
        $e->label("Test field");
        $e->required();
        $e->placeholder("Test input");
        $form->addElement($e);
        
        $e = new TextArea('textarea');
        $e->placeholder("Text texarea input");
        $form->addElement($e);
        
        $e = new Select('select');
        $e->label("Select field");
        $e->options(array('test','test2'));
        $form->addElement($e);
        
        $e = new Submit('submit');
        $form->addElement($e);
        
        if ($form->submitted()) {
            var_dump($form->getData());
        }
        
        $page->assign('content',$form->render());
        $page->wrapper->assign('foobar',"This is a test");
        
        // Return it to the browser
        return $page;
    }
}