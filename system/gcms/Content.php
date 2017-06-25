<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// Content.php
// Methods to manage and search CMS content
//
// Created: 19.08.2016 10:01:44 GMT+2
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

class Content
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
			self::$instance = true;
		}
	}
	
	
	//-----------------------------------------------------------------------------------------
	// getObjectData()
	// Get the content data of a list of specified objects
	//
	// id 	: Array of object IDs for the objects to be retrieved
	// limit :	Number of objects to return at most (for pagination)
	// offset :	Starting position (for pagination)
	//-----------------------------------------------------------------------------------------

	public static function getObjectData($list, $limit, $offset)
	{
		$result = [];
		
		$cnt_list = count($list);
		if ($cnt_list > 0)
		{
			// Build SQL query with bind parameters
			$expr = implode(',', array_fill(0, $cnt_list, '?'));
			$siteID = Settings::get('siteID');
			$stmt = DB::prepare(sprintf(
				"SELECT * FROM object WHERE (name IN (%s)) AND (siteID=%d) ORDER BY cdate DESC LIMIT :vLimit OFFSET :vOffset", $expr, $siteID
			));
			$n = 1;
			foreach ($list as $val)
			{
				DB::bind($stmt, $n, $val);
				$n ++;
			}
			
			// Bind limit and offset parameters
			DB::bind($stmt, ':vLimit', $limit);
			DB::bind($stmt, ':vOffset', $offset);
			
			// Execute prepared statement
			$rows = DB::execStatement($stmt);

			// Convert SQL result to array
			$tags = array();
			while ($row = $rows->fetchArray(SQLITE3_ASSOC))
			{
				$objID = DB::escape($row['name']);

				// Get tags for each post
				$taglist = [];
				$tags = DB::query(sprintf(
					"SELECT name FROM tag WHERE (objID='%s') AND (siteID=%d) ORDER BY name", $objID, $siteID
				));
				while ($tag = $tags->fetchArray(SQLITE3_ASSOC))
				{
					$taglist[] = $tag['name'];
				}
				$row['tags'] = $taglist;
				
				// Get attributes for each post
				$attrlist = [];
				$attrs = DB::query(sprintf(
					"SELECT name,value FROM attribute WHERE objID='%s'", $objID
				));
				while ($attr = $attrs->fetchArray(SQLITE3_ASSOC))
				{
					$attrlist[$attr['name']] = $attr['value'];
				}
				$row['attributes'] = $attrlist;
				
				// Handle "sticky" items with pseudo creation date 1000yrs in the future
				if (isset($attrlist['st']))
				{
					$row['cdate'] -= STICKY_DATE_OFFSET;
				}
				
				$result[] = $row;
			}
		}
		return $result;
	}
	
	
	//-----------------------------------------------------------------------------------------
	// getRandomID()
	// Create a random page name
	//-----------------------------------------------------------------------------------------

	public static function getRandomID()
	{
		return strtr(
			base64_encode(crc32(time().mt_rand())),
			array('+' => '', '=' => '')
		);
	}


	//-----------------------------------------------------------------------------------------
	// getDummyObject()
	// Create and return a dummy object with empty fields
	//
	// name :	Name field to be assigned to object
	//-----------------------------------------------------------------------------------------

	public static function getDummyObject($name)
	{
		return array(array(
			'name' => $name,
			'type' => 'text',
			'content' => '',
			'title' => '',
			'cdate' => time(),
			'tags' => [],
			'attributes' => [
				'nc' => 1
			]
		));
	}


	//-----------------------------------------------------------------------------------------
	// exists()
	// Checks if an object with the specified name exists
	//
	// name : name of object
	//-----------------------------------------------------------------------------------------
	
	public static function exists($name)
	{
		$escname = DB::escape($name);
		$siteID = Settings::get('siteID');
		$res = DB::query(sprintf(
			"SELECT count(*) FROM object WHERE (name='%s') AND (siteID=%d)", 
				$escname, $siteID
		), true, false);
		return ($res != 0);	
	}
	
	
	//-----------------------------------------------------------------------------------------
	// isPrivate()
	// Checks if the object with the given name is marked as private
	//
	// name : name of object
	//-----------------------------------------------------------------------------------------
	
	private static function isPrivate($objID)
	{
		$siteID = Settings::get('siteID');
		$check = DB::query(sprintf(
			"SELECT objID FROM attribute WHERE (objID='%s') AND (siteID=%d) AND (name='pv')",
			$objID, $siteID
		), true, false);
		
		return ($check == $objID);	
	}
	
	
	//-----------------------------------------------------------------------------------------
	// isUserPost()
	// Checks if the object with the given name has been created by the currently logged user
	//
	// name : name of object
	//-----------------------------------------------------------------------------------------
	
	private static function isUserPost($objID)
	{
		$user = User::loggedUser();
		if ($user != '')
		{
			$siteID = Settings::get('siteID');
			$check = DB::query(sprintf(
				"SELECT objID FROM attribute WHERE (objID='%s') AND (siteID=%d) AND (name='id') AND (value='%s')", 
				$objID, $siteID, $user
			), true, false);
			return ($check == $objID);	
		}
		else
		{
			return false;
		}
	}
	
	
	//-----------------------------------------------------------------------------------------
	// find()
	// Finds posts matching specified tags or ID
	//
	// taglist :	Array of tags to be matched
	//-----------------------------------------------------------------------------------------
	
	public static function find($taglist)
	{
		$result = [];

		// Default to homepage if taglist is empty
		if (empty($taglist))
		{
			$taglist[] = Settings::get('home');
		}
		
		// If one tag specified, check if this refers to an object or a tag
		if (count($taglist) == 1)
		{
			$id = $taglist[0];
			if (self::exists($id))
			{
				// An object with the same name as this tag exists. Return object's ID.
				if (self::isPrivate($id))
				{
					if (self::isUserPost($id))
					{
						$result[] = $id;
					}
				}
				else{
					$result[] = $id;
				}
				return $result;
			}
		}

		// More than one tag specified; created sequence of nested SQL SELECT subqueries
		$sql = '';
		$siteID = Settings::get('siteID');

		foreach ($taglist as $tag)
		{
			$sub = sprintf("SELECT objID FROM tag WHERE (name='%s') AND (siteID=%d)",
				DB::escape($tag), $siteID);
			$sql = ($sql == '') ? $sub : "$sub AND objID in ($sql)";
		}
		$rows = DB::query($sql);

		// Convert query result to array
		while ($row = $rows->fetchArray(SQLITE3_ASSOC))
		{
			$objID = $row['objID'];
			
			// Check if this is a private post
			if (self::isPrivate($objID))
			{
				if (self::isUserPost($objID))
				{
					$result[] = $objID;
				}
			}
			else{
				$result[] = $objID;
			}
		}
		return $result;
	}
	

	//-----------------------------------------------------------------------------------------
	// delete()
	//
	// Delete the specified object
	//
	// obj :	Name of object to be deleted
	//-----------------------------------------------------------------------------------------

	public static function delete($obj)
	{
		$name = DB::escape($obj);
		$siteID = Settings::get('siteID');
		
		DB::exec("BEGIN TRANSACTION");
		$res = DB::exec(sprintf(
			"DELETE FROM object WHERE (name='%s') AND (siteID=%d)", $name, $siteID
		));
		
		// Delete all tags belonging to object
		$res = $res && DB::exec(sprintf(
			"DELETE FROM tag where (objID='%s') AND (siteID=%d)", $name, $siteID
		));
		
		// Delete all attributes belonging to object
		$res = $res && DB::exec(sprintf(
			"DELETE FROM attribute where (objID='%s') AND (siteID=%d)", $name, $siteID
		));
		
		// Find all media files belonging to object
		$rows = DB::query(sprintf(
			"SELECT name FROM media WHERE (parent='%s') AND (siteID=%d)", $name, $siteID
		), false);
		
		$mediadir = Settings::get('mediaDir') . '/';
		while ($row = $rows->fetchArray(SQLITE3_ASSOC))
		{
			// Delete each media file from filesystem
			$path = $mediadir . $row['name'];
			if (file_exists($path)) unlink($path);
		}
		
		// Delete all attached media files from database
		$res = $res && DB::exec(sprintf(
			"DELETE FROM media WHERE (parent='%s') AND (siteID=%d)", $name, $siteID
		));

		// Check for errors
		if ($res)
		{
			DB::exec("COMMIT");
			return array(true, '');
		}
		else
		{
			$msg = DB::lastError();
			DB::exec("ROLLBACK");
			return array(false, $msg);
		}
	}
	
	
	//-----------------------------------------------------------------------------------------
	// store()
	// Store the specified object data
	//
	// obj :	Associative array representing object to be stored
	//
	// Returns a list(status=true|false, message). If an error occured, then status=false
	// and message contains error message.
	//-----------------------------------------------------------------------------------------

	public static function store($obj)
	{
		$msg = [];
		
		// Field validation
		if (! in_array($obj['type'], array('text', 'html')))
		{
			array_push($msg, '* Invalid content type');
		}
		if (trim($obj['content']) == '')
		{
			array_push($msg, '* Article content is empty');
		}
		if (trim($obj['title']) == '')
		{
			array_push($msg, '* Article title is empty');
		}
		
		// Validate post date
		preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{2,4})/', $obj['cdate'], $matches);
		if (count($matches) != 4)
		{
			array_push($msg, '* Invalid post date');
		}
		else
		{
			array_shift($matches);
			list($dd, $mm, $yy) = $matches;
			if ($yy < 100) {
				$yy += 2000;
			}
		}
		
		// Validate post time
		preg_match('/(\d{1,2}):(\d{1,2}):(\d{1,2})/', $obj['cdate'], $matches);
		$cnt_matches = count($matches);
		if ($cnt_matches > 0)
		{
			if ($cnt_matches != 4)
			{
				array_push($msg, '* Invalid post time');
			}
			else
			{
				array_shift($matches);
				list($thh, $tmm, $tss) = $matches;
			}
		}
		else
		{
			// No time specified
			$thh = 0; $tmm = 0; $tss = 0;
		}
		
		if (! checkdate($mm, $dd, $yy))
		{
			array_push($msg, '* Invalid date');
		}
		else
		{
			// Make UNIX time
			$mkt = mktime($thh, $tmm, $tss, $mm, $dd, $yy);
			
			// Handle sticky posts by advancing the post date into the distant future
			$obj['cdate'] = isset($obj['attributes']['st']) ? 
				$mkt + STICKY_DATE_OFFSET : $mkt;
		}
		
		// Validate tag list
		foreach ($obj['tags'] as $key => $t)
		{
			$t = preg_replace("/[^a-zA-Z0-9_aöüÄÖÜ-]/", "", $t);
			if ($t == '')
			{
				unset($obj['tags'][$key]);
			}
			elseif (self::exists($t))
			{
				// Tag collides with existing object ID
				array_push($msg, "* Tag '$t' collides with existing object name");
			}
			else
			{
				// Replace tag by trimmed version
				$obj['tags'][$key] = $t;
			}
		}
		if (empty($obj['tags']))
		{
			$obj['tags'] = ['untagged'];
		}

		// Check if any validation errors occured
		if (! empty($msg))
		{
			return array(false, implode("\n", $msg) . "\n\n");
		}
		
		// Store object in database
		$name = DB::escape($obj['name']);
		$siteID = Settings::get('siteID');
		
		DB::exec("BEGIN TRANSACTION");
		$res = DB::exec(sprintf(
			"REPLACE INTO object (name,type,content,title,cdate,siteID) VALUES ('%s','%s','%s','%s','%s',%d)",
			$name,
			DB::escape($obj['type']),
			DB::escape($obj['content']),
			DB::escape($obj['title']),
			DB::escape($obj['cdate']),
			$siteID
		));
		
		// Delete all previous tags belonging to object
		$res = $res && DB::exec(sprintf(
			"DELETE FROM tag where (objID='%s') AND (siteID=%d)", $name, $siteID
		));
		
		// Store current tags
		foreach ($obj['tags'] as $key => $t)
		{
			$res = $res && DB::exec(sprintf(
				"INSERT INTO tag (objID,name,siteID) VALUES ('%s','%s',%d)",
				$name,
				DB::escape($t),
				$siteID
			));
		}
		
		// Delete all previous attributes belonging to object
		$res = $res && DB::exec(sprintf(
			"DELETE FROM attribute where (objID='%s') AND (siteID=%d)", 
			$name, $siteID
		));		
		
		// Store current attributes
		foreach ($obj['attributes'] as $key => $t)
		{
			$res = $res && DB::exec(sprintf(
				"INSERT INTO attribute (objID,name,value,siteID) VALUES ('%s','%s','%s',%d)",
				$name,
				DB::escape($key),
				DB::escape($t),
				$siteID
			));
		}
		
		// Attach all draft media permanently to object
		$res = $res && DB::exec(sprintf(
			"UPDATE media SET draft=0 WHERE (parent='%s') AND (siteID=%d)", 
			$name, $siteID
		));
		
		// Delete any stale draft media leftover from previous aborted edits
		$res = $res && DB::exec(sprintf(
			"DELETE FROM media WHERE draft=1 AND cdate<%d", time() - 86400
		));
		
		// Check for errors
		if ($res)
		{
			DB::exec("COMMIT");
			return array(true, '');
		}
		else
		{
			$msg = DB::lastError();
			DB::exec("ROLLBACK");
			return array(false, $msg);
		}
	}


	//-----------------------------------------------------------------------------------------
	// error()
	// Create a pseudo object containing an error message
	//
	// message :	Error message
	//-----------------------------------------------------------------------------------------

	public static function error($message)
	{
		return self::notify("GCMS Error", "<p>$message</p>" . 
			'<p><a href="javascript:window.history.back()">Return to previous page</a></p>'
		);
	}
	
	
	//-----------------------------------------------------------------------------------------
	// notify()
	// Create a pseudo object containing a notification message
	//
	// message :	Error message
	//-----------------------------------------------------------------------------------------

	public static function notify($title, $message)
	{
		$obj = self::getDummyObject('error');
		$obj[0]["content"] = $message;
		$obj[0]["title"] = $title;
		return $obj;
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