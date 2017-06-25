<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// CustomError.php
// Custom PHP Error Handlers
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

class CustomError
{
	private static $instance;
	
	public static function initialize()
	{
		// Only execute initialization routine once
		if (! isset(self::$instance))
		{
			// Install own error handler
			$me = get_class();
			set_error_handler(array($me, 'ErrorHandler'));
			set_exception_handler(array($me, 'ExceptionHandler'));
			register_shutdown_function(array($me, 'ShutdownHandler'));

			self::$instance = true;
		}
	}
	
	public static function ErrorHandler($errno, $errstr, $errfile, $errline)
	{
		echo "<h1>GCMS Error</h1>";
		echo "<p><b>$errstr</b></p><p>($errfile : $errline)</p>";
		exit;
	}

	public static function ExceptionHandler($exception)
	{
		echo "<h1>GCMS Exception</h1>";
		echo "<pre>" . print_r($exception, true) . "</pre>";
		exit;
	}
	
	public static function ShutdownHandler()
	{
		$error = error_get_last();
		if ( $error["type"] == E_ERROR )
		{
			echo "<h1>GCMS Shutdown</h1>";
			echo "<pre>" . print_r(array(
				$error["type"],
				$error["message"],
				$error["file"],
				$error["line"]
			), true) . "</pre>";
		}
	}
}

?>