<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// Settings.php
// Methods to set and retrieve system configuration values
//
// Created: 17.08.2016 06:20:16 GMT+2
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

require_once(DIR_FRAMEWORK . 'Mobile_Detect.php');

class Settings
{
	private static $instance;
	private static $data;
	
	//-----------------------------------------------------------------------------------------
	// initialize()
	// Class Initialization
	//-----------------------------------------------------------------------------------------
	
	public static function initialize()
	{
		global $SITE_PARAMETER;
		
		// Only execute initialization routine once
		if (! isset(self::$instance))
		{
			// Get site configuration
			$site = Path::get('site');
			include DIR_CFG . '/site.cfg';
			
			// Check if site settings exist
			if (! isset($SITE_CONFIG[$site]))
			{
				trigger_error("Site '$site' not configured");
			}			
			$domain = "http://$site";
			$basedir = Path::get('baseDir');
			self::$data = $SITE_CONFIG[$site];
			self::$data['domainURL'] = $domain . $basedir;
			self::$data['baseURL'] = $domain . Path::get('baseURL');
			self::$data['baseDir'] = $basedir;
			self::$data['version'] = GCMS_VERSION;
			
			// Check if site called from a mobile browser
			$browser = new Mobile_Detect;
			self::$data['mobile'] = $browser->isMobile();
			
			self::$instance = true;
		}
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
			trigger_error(_("Invalid call") . ": get('$var')");
		}
	}
}

?>