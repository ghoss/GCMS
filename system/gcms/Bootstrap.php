<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// Bootstrap.php
// Loader for classes at startup, and language internationalization
//
// Created: 15.08.2016 23:43:40 GMT+2
//=============================================================================================
// Copyright (C) 2016-2017 by Guido Hoss
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
	// Translation table to convert language returned by browser to canonical form
	private static $LANG = [
		'de' => 'de_DE'
	];
	
	public static function initialize()
	{
		// Initialize language translation
		if (function_exists('gettext'))
		{
			$domain = 'gcms';
			$locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$locale = isset(Bootstrap::$LANG[$locale]) ? Bootstrap::$LANG[$locale] : 'en_US';
			setlocale(LC_MESSAGES, $locale);
			bindtextdomain($domain, DIR_S . '/locale');	
			textdomain($domain);
		}
		else
		{
			trigger_error('gettext is not supported');
		}
		
		// Initialize autoloader
		$ok = spl_autoload_register(function($class) {
			include DIR_GCMS . $class . '.php';
			$class::initialize();
		});
		
		$ok or trigger_error('Could not register autoloader');
	}
}

?>