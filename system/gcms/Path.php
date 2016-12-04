<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// Path.php
// Methods to extract requested objects and paramters from CGI request
//
// Created: 17.08.2016 00:00:56 GMT+2
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

class Path
{
	private static $instance;
	private static $data;
	
	
	//-----------------------------------------------------------------------------------------
	// initialize()
	// Class Initialization
	//-----------------------------------------------------------------------------------------
	
	public static function initialize()
	{
		// Only execute initialization routine once
		if (! isset(self::$instance))
		{
			// Get name of site
			if (isset($_SERVER['HTTP_HOST']))
			{
				self::$data['site'] = $_SERVER['HTTP_HOST'];
			}
			elseif (isset($_SERVER['SERVER_NAME']))
			{
				self::$data['site'] = $_SERVER['SERVER_NAME'];
			}
			else
			{
				trigger_error('Unable to determine site name');
			}
			
			// Get script basename
			$base = $_SERVER['SCRIPT_NAME'];
			self::$data['baseURL'] = $base;
			
			// Get script base directory
			$pos = strrpos($base, '/', 0);
			self::$data['baseDir'] = substr($base, 0, $pos);
						
			// Get requested object
			$object = substr(strstr($_SERVER['PHP_SELF'], $base), strlen($base) + 1);
			
			// Split object into tags delimited by '/'
			$taglist = explode('/', $object);
			
			// Remove empty tags from array and sanitize the non-empty ones
			foreach ($taglist as $key => $tag)
			{
				$tag = self::sanitize($tag);
				if ($tag == '')
				{
					unset($taglist[$key]);
				}
				else
				{
					$taglist[$key] = $tag;
				}
			}
			self::$data['request'] = $taglist;
			
			// Get action and optional params, if any
			foreach(["action", "tags", "page"] as $x)
			{
				self::$data[$x] = isset($_REQUEST[$x]) ? $_REQUEST[$x] : '';
			}
			
			self::$instance = true;
		}
	}
	
	
	//-----------------------------------------------------------------------------------------
	// sanitize()
	// Removes special characters from a URI or string destined to be a URI
	//-----------------------------------------------------------------------------------------
	
	public static function sanitize($str)
	{
		$str = str_replace(array('[\', \']'), '', $str);
		$str = preg_replace('/\[.*\]/U', '', $str);
		$str = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '-', $str);
		$str = htmlentities($str, ENT_COMPAT, 'utf-8');
		$str = preg_replace(
			'/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);/i',
			'\\1', $str
		);
		$str = preg_replace(array('/[^a-z0-9]/i', '/[-]+/') , '-', $str);
		
		return strtolower(trim($str, '-'));		
	}
	

	//-----------------------------------------------------------------------------------------
	// get()
	// Getter for private class variables
	//-----------------------------------------------------------------------------------------
	
	public static function get($var)
	{
		if (isset(self::$data[$var]))
		{
			return self::$data[$var];
		}
		else
		{
			trigger_error("Invalid call: get('$var')");
		}
	}
}

?>