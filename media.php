<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// media.php
// Media upload and display handler
//
// Created: 16.11.2016 18:29:28 GMT+1
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

require_once('config/gcms.cfg');
require_once(DIR_GCMS . 'Bootstrap.php');

// Register autoloader for GCMS classes
Bootstrap::initialize();

// Get requested action
$action = Path::get('action');

// Get login status and check user credentials if logged in
if (isset($_COOKIE[SESSION_COOKIE]))
{
	User::validate($_COOKIE[SESSION_COOKIE], $timeout, false);
}

// Must be logged in user to continue
if (User::loggedUser() != '')
{
	// Action dispatcher
	switch ($action)
	{
		case 'upload' :
			$result = Media::upload();
			break;
		
		case 'delete' :
			$result = Media::delete();
			break;
			
		case 'getlist' :
			$result = Media::getList();
			break;
		
		default :
			$result = [0, 'Not implemented'];
			break;
	}
}
else
{
	$result = [0, _('Invalid user')];
}

// Render result
echo json_encode(['success' => $result[0], 'msg' => $result[1]]);

?>