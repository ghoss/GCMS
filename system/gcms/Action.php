<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// Action.php
// Execution of actions requested from index.php
//
// Created: 16.11.2016 11:20:37 GMT+1
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

class Action
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
	// getObjects()
	//
	// Helper function to get the list of requested objects and the associcated object count.
	//-----------------------------------------------------------------------------------------

	private static function getObjects($request)
	{
		$objects = Content::find($request);
		$cnt_objects = count($objects);
		return [$objects, $cnt_objects];
	}
	
	
	//-----------------------------------------------------------------------------------------
	// show()
	//
	// Implementation of action "show".
	//-----------------------------------------------------------------------------------------

	public static function show($request)
	{
		// Only retrieve objects to which we have access
		// TODO: Needs to be implemented on a DB query level
		list($objects, $cnt_objects) = self::getObjects($request);
		$flags = [];
		
		foreach ($objects as $key => $obj)
		{
			if (User::access($obj, 'show'))
			{
				// Check for edit rights
				if (User::access($obj, 'edit'))
				{
					$flags["acl_edit"] = true;
				}
			}
			else
			{
				// No read access for this object
				unset($objects[$key]);
			}
		}
		
		// Get revised count of objects as some where possibly removed
		$cnt_objects = count($objects);

		if ($cnt_objects > 0)
		{
			// Handle pagination
			$page = Path::get('page');
			$limit = Settings::get('pagination');
			$numpages = intdiv($cnt_objects + $limit - 1, $limit);
			
			if ($page <= 0)
			{
				$page = 1;
			}
			elseif (($page - 1) * $limit > $cnt_objects)
			{
				$page = $numpages;
			}
			if ($page > 1) 
			{
				$flags['prevPage'] = ($page - 1);
				$flags['pagination'] = true;
			}
			if ($page * $limit < $cnt_objects)
			{
				$flags['nextPage'] = ($page + 1);
				$flags['pagination'] = true;
			}
			$offset = ($page - 1) * $limit;
			
			// Set total number of pages and current page
			$flags += [
				'numPages' => $numpages,
				'thisPage' => $page
			];
			
			// Retrieve content for all objects
			if ($cnt_objects == 1)
			{
				$flags["singlePost"] = true;
			}
			$content = Content::getObjectData($objects, $limit, $offset);
		}
		elseif ((count($request) == 1) && (User::access(-1, 'edit')))
		{
			// Object does not exist. If edit rights, open page in edit mode
			Template::redirect($request[0], 'edit');
		}
		else
		{
			// Object does not exist, and no edit rights. Show error instead.
			$content = Content::error(_("Invalid resource specified"));
		}
		return [$content, $flags];
	}
	
	
	//-----------------------------------------------------------------------------------------
	// edit()
	//
	// Implementation of action "edit".
	//-----------------------------------------------------------------------------------------

	public static function edit($request)
	{
		list($objects, $cnt_objects) = self::getObjects($request);
		$flags = [];
		$action = 'edit';

		if ($cnt_objects <= 1)
		{
			if (User::access(($cnt_objects == 1) ? $objects[0] : -1, $action))
			{
				// Change to edit mode
				$flags["editmode"] = true;
				$flags["singlePost"] = true;
				if ($cnt_objects == 0)
				{
					if (count($request) == 1)
					{
						// Object does not exist yet, create an empty dummy object to edit
						$content = Content::getDummyObject($request[0]);
						$content[0]["attributes"] = [];
						$tags = Path::get('tags');
						if ($tags != '')
						{
							$content[0]['tags'] = explode(',', $tags);
						}
					}
					else
					{
						$content = Content::error(
							sprintf(_("Invalid resource specified for action '%s'"), $action)
						);
					}
				}
				else
				{
					// Retrieve the content to be edited
					$content = Content::getObjectData($objects, 1, 0); 
				}
			}
			else
			{
				$content = Content::error(
					sprintf(_("Action '%s' not allowed for this object."), $action)
				);
			}
		}
		else
		{
			// Can't edit multiple objects
			$content = Content::error(
				sprintf(_("Action '%s' can only be applied to a single object."), $action)
			);
		}
		return [$content, $flags];
	}

	
	//-----------------------------------------------------------------------------------------
	// new()
	//
	// Implementation of action "new".
	//-----------------------------------------------------------------------------------------

	public static function newObject()
	{
		// Create a random page name and redirect to it in edit mode
		if (User::access(-1, 'edit'))
		{
			$uuid = Content::getRandomID();
			$tags = Path::get('tags');
			Template::redirect($uuid, "edit&tags=$tags");
		}
		else
		{
			$content = Content::error(
				sprintf(_("Action '%s' not allowed."), $action)
			);
		}
		return $content;
	}


	//-----------------------------------------------------------------------------------------
	// store()
	//
	// Implementation of action "store".
	//-----------------------------------------------------------------------------------------

	public static function store($request)
	{
		list($objects, $cnt_objects) = self::getObjects($request);

		if ($cnt_objects <= 1)
		{
			if (User::access(($cnt_objects == 1) ? $objects[0] : -1, 'edit'))
			{
				if (($cnt_objects == 1) || (count($request) == 1))
				{
					$name = ($cnt_objects == 1) ? $objects[0] :	$request[0];
					$attrlist = isset($_POST['attr']) ? $_POST['attr'] : [];
					
					// Add current user as post owner to attributes
					$attrlist['id'] = User::loggedUser();
					
					// Check if featured image is set
					if (! (isset($attrlist['ft']) && ($attrlist['ft'] != '')))
					{
						unset($attrlist['ft']);
					}
							
					list($success, $msg) = Content::store(array(
						'name' => $name,
						'type' => 'text',
						'cdate' => isset($_POST['postDate']) ? $_POST['postDate'] : '',
						'content' => isset($_POST['postContent']) ? $_POST['postContent'] : '',
						'title' => isset($_POST['postTitle']) ? $_POST['postTitle'] : '',
						'tags' => explode(',', isset($_POST['postTags']) ? $_POST['postTags'] : ''),
						'attributes' => $attrlist
					));
					if ($success)
					{
						// Update successful, redirect to updated page
						Template::redirect($name, 'show');
					}
					else
					{
						// An error occured; display returned error message
						$content = Content::error($msg);
					}
				}
				else
				{
					$content = Content::error(
						sprintf(_("Invalid resource specified for action '%s'"), $action)
					);
				}
			}
			else
			{
				$content = Content::error(
					sprintf(_("Action '%s' not allowed for this object."), $action)
				);
			}
		}
		else
		{
			// Can't edit multiple objects
			$content = Content::error(
				sprintf(_("Action '%s' can only be applied to a single object."), $action)
			);
		}
		return $content;
	}
	

	//-----------------------------------------------------------------------------------------
	// delete()
	//
	// Implementation of action "delete".
	//-----------------------------------------------------------------------------------------

	public static function delete($request)
	{
		list($objects, $cnt_objects) = self::getObjects($request);

		if ($cnt_objects == 1)
		{
			$id = $objects[0];
			if (User::access($id, 'edit'))
			{
				list($success, $msg) = Content::delete($id);
				if ($success)
				{
					// Update successful, redirect to homepage
					Template::redirect('', 'show');
				}
				else
				{
					// An error occured; display returned error message
					$content = Content::error($msg);
				}
			}
			else
			{
				$content = Content::error(
					sprintf(_("Action '%s' not allowed for this object."), $action)
				);
			}
		}
		else
		{
			// Can't delete multiple objects
			$content = Content::error(
				sprintf(_("Action '%s' can only be applied to a single object."), $action)
			);
		}
		return $content;
	}


	//-----------------------------------------------------------------------------------------
	// login()
	//
	// Implementation of action "login".
	//-----------------------------------------------------------------------------------------

	public static function login()
	{
		// Precede each login by a mandatory logout
		self::logoutHelper();
		
		// Check for user credentials
		if (isset($_POST['hash']))
		{
			// Credentials supplied; verify them
			$sessionID = User::validateLogin($_POST['hash']);
			if ($sessionID != '')
			{
				// Successful login
				setcookie(SESSION_COOKIE, $sessionID, 0, Path::get('baseDir'));
				$msg = sprintf(_("You are now logged in as user '%s'."), User::loggedUser());
				session_start();
				if (isset($_SESSION['refer']))
				{
					// Provide link to resubmit previous request
					$msg .= sprintf("<form method='POST' action='%s'>", $_SESSION['request']);
					foreach ($_SESSION['data'] as $key => $val)
					{
						$msg .= sprintf("<input type='hidden' name='%s' value='%s' />",
							htmlspecialchars($key), htmlspecialchars($val));
					}
					$msg .= "<input type='submit' value='" . _('Continue') . "' /> " .
						_("Click here to continue with previous request") . "</form>";
				}					
				session_destroy();
				$content = Content::notify(_("Login Successful"), $msg);		
			}
			else
			{
				$content = Content::error(_("Invalid username or password."));		
			}
		}
		else
		{
			// No credentials supplied; show login page
			$content = Content::getDummyObject('login');
			$content[0]['title'] = isset($_SESSION['refer']) ? 
				"Session Expired" : "User Login";
			$content[0]['type'] = 'html';
			$content[0]['content'] = Template::render(Settings::get('loginTheme'));
		}
		return $content;
	}


	//-----------------------------------------------------------------------------------------
	// profile()
	//
	// Implementation of action "profile".
	//-----------------------------------------------------------------------------------------

	public static function profile()
	{
		$user = User::loggedUser();
		
		// User must be logged in
		if ($user != '')
		{
			$changed = false;
			
			// Check for password change
			if (isset($_POST['oldhash']) && (isset($_POST['newhash'])))
			{
				$oldhash = $_POST['oldhash'];
				$newhash = $_POST['newhash'];
				
				if (($oldhash != '') && ($newhash !=''))
				{
					$res = User::updateCredentials($_POST['oldhash'], $_POST['newhash']);
					if ($res)
					{
						$content = Content::notify(
							_("Password Changed"), 
							_("The password was updated successfully. Please log in again.")
						);
						$changed = true;		
					}
					else
					{
						$content = Content::error(_("You entered an invalid password."));		
					}
				}
			}
			
			// Check for timeout value change
			if (isset($_POST['timeout']))
			{
				$timeout = $_POST['timeout'] + 0;
				if ($timeout > 0)
				{
					User::updateAttributes(['timeout' => $timeout]);
					$changed = true;
				}
				else
				{
					$content = Content::error(_("The timeout value specified is invalid."));		
				}
			}
			
			// If nothing changed or specified, then display profile form
			if ((! $changed) && empty($content))
			{
				// No credentials supplied; show profile page
				$content = Content::getDummyObject('profile');
				$content[0]['title'] = sprintf(_("Profile Settings for '%s'"), $user);
				$content[0]['type'] = 'html';
				$content[0]['content'] = Template::render(Settings::get('profileTheme'));
			}
			elseif (empty($content))
			{
				$content = Content::notify(
					_("Settings Updated"), 
					_("Your profile settings were updated successfully.")
				);
			}
		}
		else
		{
			$content = Content::error(_("No valid user session."));		
		}
		return $content;
	}


	//-----------------------------------------------------------------------------------------
	// logout()
	//
	// Implementation of action "logout".
	//-----------------------------------------------------------------------------------------

	public static function logout()
	{
		if (User::loggedUser() != '')
		{
			// Clear session in database and erase cookie
			self::logoutHelper();
			session_start();
			session_destroy();
			$content = Content::notify(_("User Logout"), 
				_("You have been successfully logged out."));
		}
		else
		{
			$content = Content::error(_("No valid user session."));		
		}
		return $content;
	}


	//-----------------------------------------------------------------------------------------
	// logoutHelper()
	//
	// Helper function for logout procedure.
	//-----------------------------------------------------------------------------------------

	public static function logoutHelper()
	{
		User::invalidate();
		setcookie(SESSION_COOKIE, '', 1, Path::get('baseDir'));
	}
}

?>