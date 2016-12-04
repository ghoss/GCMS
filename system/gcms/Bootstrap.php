<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// Bootstrap.php
// Loader for classes at startup
//
// Created: 15.08.2016 23:43:40 GMT+2
//=============================================================================================
// Copyright (C) 2016 by Guido Hoss
//
// GCMS is free software: you can redistribute it and/or 
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation, either version 3
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public
// License along with this program.  If not, see
// <http://www.gnu.org/licenses/>.
//
// Git repository home: <https://github.com/ghoss/GCMS>
//=============================================================================================

class Bootstrap
{
	public static function initialize()
	{
		$ok = spl_autoload_register(function($class) {
			include DIR_GCMS . $class . '.php';
			$class::initialize();
		});
		
		$ok or trigger_error('Could not register autoloader');
	}
}

?>