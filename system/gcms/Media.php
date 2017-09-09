<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// Media.php
// Execution of actions requested from media.php
//
// Created: 16.11.2016 18:59:24 GMT+1
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

class Media
{
	private static $instance;
	
	
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
	// upload()
	//
	// Implementation of action "upload".
	//-----------------------------------------------------------------------------------------

	public static function upload()
	{
		$status = 0;
		$errors = [];
		$okfiles = [];
		
		// Check if the parent of this media file has been specified
		if (! isset($_REQUEST['objectId']))
		{
			return [0, _("Invalid request")];
		}
		else
		{
			$parent = $_REQUEST['objectId'];
		}
		
		// Loop through each uploaded file
		foreach($_FILES as $index => $file)
		{
			$fileTempName = $file['tmp_name'];
			$msg = '';
			
			// Check if there is an error for particular entry in array
			if (! empty($file['error']))
			{
				switch ($file['error'])
				{
					case UPLOAD_ERR_OK:
						break;
						
					case UPLOAD_ERR_NO_FILE:
						$msg = _('No file received from client');
						break;
						
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						$msg = _('Exceeded filesize limit');
						break;
						
					default:
						$msg = _('Unknown error during transmission');
						break;
				}
				
				if ($msg != '')
				{
					$errors[] = $file['name'] . ": $msg";
				}
			}
 
			// check whether file has temporary path and whether it indeed is an uploaded file
			if ((! empty($fileTempName)) && is_uploaded_file($fileTempName))
			{
				// Get dimensions of image file, if available
				list($iWidth, $iHeight) = getimagesize($fileTempName);
				
				// Assign a unique filename for server storage
				$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
				$fileName = Content::getRandomID() . ".$ext";

				// Move the file from temp storage to the media directory
				$res = move_uploaded_file(
					$fileTempName, 
					Settings::get('mediaDir') . "/$fileName"
				);
				
				if ($res)
				{
					// Insert entry for this file into database
					$siteID = Settings::get('siteID');
					DB::exec("BEGIN TRANSACTION");
					DB::exec(sprintf(
						"INSERT INTO media (name,parent,draft,cdate,width,height,siteID) " .
						"VALUES ('%s','%s',%d,%d,%d,%d,%d)",
						DB::escape($fileName), DB::escape($parent), 1, time(),
						$iWidth, $iHeight, $siteID
					));
					DB::exec("COMMIT");
					
					// File info to be passed back to Ajax caller
					$okfiles[] = [
						'index' => $index,
						'name' => $fileName,
						'ext' => $ext,
						'seq' => $_REQUEST['seq']
					];
				}
				else
				{
					$errors[] = _("File storage on server failed");
				}
				// TODO
				// Assign URID to uploaded file and associate it with object in DB w/tmp status
			}
		}
		
		// Check if any files were uploaded at all
		if (empty($okfiles))
		{
			$errors[] = _("No files were uploaded");
		}
	
		// Check for errors
		if (! empty($errors))
		{
			// Return list of errors encountered
			return [0, implode("\n", $errors)];
		}
		else
		{
			// TODO
			// Load list of files attached to object from DB and put it into $oklist

			// Return list of successfully uploaded files
			return [1, $okfiles];
		}
	}
	
	
	//-----------------------------------------------------------------------------------------
	// delete()
	//
	// Implementation of action "delete".
	//-----------------------------------------------------------------------------------------

	public static function delete()
	{
		// Check if the ID of the media file has been specified
		if (! isset($_REQUEST['id']))
		{
			return [0, _("Invalid request")];
		}
		else
		{
			// Sanitize media filename for filesystem use
			$id = $_REQUEST['id'];
			$name = str_replace('/', '_', $id);
		}

		$escname = DB::escape($name);
		$siteID = Settings::get('siteID');
		DB::exec("BEGIN TRANSACTION");

		// Delete media file from database
		DB::exec(sprintf(
			"DELETE FROM media WHERE (name='%s') AND (siteID=%d)",
			$escname, $siteID
		));
		
		// Delete media file from featured attribute table
		DB::exec(sprintf(
			"DELETE FROM attribute WHERE (siteID=%d) AND (name='ft') AND (value='%s')",
			$siteID, $escname
		));

		DB::exec("COMMIT");		
		
		// Delete media file from storage
		$path = Settings::get('mediaDir') . "/$name";
		if (file_exists($path)) unlink($path);
		
		return [1, $id];
	}


	//-----------------------------------------------------------------------------------------
	// getList()
	//
	// Implementation of action "getlist".
	//-----------------------------------------------------------------------------------------

	public static function getList()
	{
		// Check if object ID has been specified
		if (! isset($_REQUEST['id']))
		{
			return [0, _("Invalid request")];
		}
		else
		{
			$id = $_REQUEST['id'];
		}

		// Get media IDs associated with object
		$siteID = Settings::get('siteID');
		$media = [];
		$rows = DB::query(sprintf(
			"SELECT name FROM media WHERE (parent='%s') AND (siteID=%d)", 
			DB::escape($id), $siteID
		));
		
		while ($row = $rows->fetchArray(SQLITE3_ASSOC))
		{
			$media[] = [
				'name' => $row['name'],
				'ext' => pathinfo($row['name'], PATHINFO_EXTENSION)
			];
		}

		return [1, $media];
	}
}

?>