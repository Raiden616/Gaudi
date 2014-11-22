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

chdir("classes");

// Main model library
require_once("sql.php");
require_once("model.php");

//require_once("misc.php");

require_once("html.php");
require_once("view.php");

//require_once("form.php");
//require_once("formvalidator.php");
//require_once("tabledisplay.php");

//require_once("user.php");

//require_once("file.php");

require_once("error.php");


chdir("../");

?>