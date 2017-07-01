<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// User.php
// Login and access control methods
//
// Created: 21.08.2016 10:57:55 GMT+2
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

class User
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
			self::set('username', '');
			self::$instance = true;
		}
	}
	

	//-----------------------------------------------------------------------------------------
	// loggedUser()
	// 
	// Return name of current logged in user, or empty string if user not logged in.
	//-----------------------------------------------------------------------------------------
	
	public static function loggedUser()
	{
		// Only execute initialization routine once
		return self::get('username');	
	}
	

	//-----------------------------------------------------------------------------------------
	// access()
	// Determine access rights for specified object and access mode. Returns TRUE if access
	// mode permitted, FALSE otherwise.
	// 
	// objID :	Object ID; (-1) is the empty object
	// action :	Action requested by user: "show", "edit"
	//-----------------------------------------------------------------------------------------
	
	public static function access($objID, $action = 'show')
	{
		// TODO
		// Currently, all modification actions are allowed for logged in users
		switch ($action)
		{
			case 'show' :
				return true;
				break;
				
			case 'edit' :
				return (self::loggedUser() != '');
				break;
				
			default :
				trigger_error(sprintf(_("Unknown action '%s'"), $action));
				break;
		}
	}
	

	//-----------------------------------------------------------------------------------------
	// validate()
	//
	// Determine if user has logged in, and if his session ID is valid. If the session is
	// valid, the logged in user will be set.
	//
	// If "expire" = false (default: true), the expiration time will not be incremented in
	// the database.
	//-----------------------------------------------------------------------------------------
	
	public static function validate($session, &$timeout, $expire = true)
	{
		DB::exec("BEGIN TRANSACTION");
		$timeout = false;
		$row = DB::query(
			sprintf(
				"SELECT session.username,session.exptime,user.timeout FROM session,user WHERE (session.sessionID='%s') AND (session.username=user.username)", 
				DB::escape($session)
			),
			true, true
		);
		
		if (count($row) > 0)
		{
			// Session is valid only if not expired yet
			$now = time();
			if ($row['exptime'] > $now)
			{
				// Login successful
				$user = $row['username'];
				$timeout_val = $row['timeout'];
				self::set('username', $user);
				self::set('timeout', $timeout_val);
				
				if ($expire)
				{
					// Update expiration time for all sessions of current user
					DB::exec(sprintf(
						"UPDATE session SET exptime=%d WHERE (username='%s') AND (exptime>%d)",
						$now + $timeout_val, $user, $now
					));		
				}		
			}
			else
			{
				// Login was valid, but session timed out
				$timeout = true;
			}
		}
		DB::exec("COMMIT");
	}
	

	//-----------------------------------------------------------------------------------------
	// invalidate()
	// 
	// Invalidate the current user session.
	//-----------------------------------------------------------------------------------------
	
	public static function invalidate()
	{
		$user = self::loggedUser();
		if ($user != '')
		{
			DB::exec("BEGIN TRANSACTION");
			DB::exec(sprintf(
				"DELETE FROM session WHERE username='%s'",
				DB::escape($user)
			));
			DB::exec("COMMIT");
			self::set('username', '');
		}
	}
	

	//-----------------------------------------------------------------------------------------
	// updateCredentials
	//
	// Change the user's password in the database, i.e. update the username/pw hash.
	//
	// oldhash :	Previous username/pw hash in DB
	// newhash :	New username/pw hash
	//
	// The function returns TRUE if the update could be performed, and FALSE if the old
	// password did not match since there was no such username/pw hash in the database.
	//
	// The session is automatically invalidated.
	//-----------------------------------------------------------------------------------------

	public static function updateCredentials($oldhash, $newhash)
	{
		DB::exec("BEGIN TRANSACTION");
		$res = DB::exec(sprintf(
			"UPDATE user SET password='%s' WHERE password='%s'",
			DB::escape($newhash),
			DB::escape($oldhash)
		));
		
		// Check if the database entry was changed successfully
		if ($res && (DB::modifiedRows() == 1))
		{
			DB::exec("COMMIT");
			self::set('username', '');
		}
		else
		{
			DB::exec("ROLLBACK");
			$res = false;
		}
		return $res;
	}
	
	
	//-----------------------------------------------------------------------------------------
	// updateAttributes
	//
	// Change the user's attributes in the database.
	//
	// attrlist :	Key/value array of attributes to change
	//-----------------------------------------------------------------------------------------

	public static function updateAttributes($attrlist)
	{
		$res = false;
		
		// Currently, only timeout attribute is supported
		if (isset($attrlist['timeout']))
		{
			$timeout = $attrlist['timeout'];

			DB::exec("BEGIN TRANSACTION");
			$res = DB::exec(sprintf(
				"UPDATE user SET timeout='%s' WHERE username='%s'",
				DB::escape($timeout), self::loggedUser()
			));
		
			// Check if the database entry was changed successfully
			if ($res && (DB::modifiedRows() == 1))
			{
				DB::exec("COMMIT");
			}
			else
			{
				DB::exec("ROLLBACK");
				$res = false;
			}
		}
		return $res;
	}
	
	
	//-----------------------------------------------------------------------------------------
	// validateLogin()
	//
	// Check the supplied username/pw hash against the user database. If the credentials
	// match, then return a session ID which can be passed to validate() in subsequent 
	// page requests. In the case of no match, return an empty string.
	//
	// hash :	MD5 username/pw hash supplied by calling login script
	//-----------------------------------------------------------------------------------------
	
	public static function validateLogin($hash)
	{
		DB::exec("BEGIN TRANSACTION");
		$row = DB::query(
			sprintf(
				"SELECT username,timeout FROM user WHERE password='%s'", 
				DB::escape($hash)
			),
			true, true
		);
		
		$sessionID = '';
		if (count($row) > 0)
		{
			// Login successful
			$user = $row['username'];
			$timeout = $row['timeout'];
			self::set('username', $user);
			self::set('timeout', $timeout);
			
			// Assign new session ID
			$sessionID = hash(AUTH_CIPHER, mt_rand());
			DB::exec(sprintf(
				"REPLACE INTO session (username,sessionID,exptime) VALUES('%s','%s',%d)",
				$user, $sessionID, time() + $timeout
			));
		}
		DB::exec("COMMIT");
		return $sessionID;
	}
	

	//-----------------------------------------------------------------------------------------
	// set()
	// Setter for private class variables
	//-----------------------------------------------------------------------------------------
	
	private static function set($var, $value)
	{
		self::$data[$var] = $value;
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