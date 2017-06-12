<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// index.php
// Main Page Handler
//
// Created: 15.08.2016 23:43:40 GMT+2
//=============================================================================================
// Copyright: (c) 2016 by Guido Hoss. All Rights Reserved.
//=============================================================================================

require_once('config/gcms.cfg');
require_once(DIR_GCMS . 'Bootstrap.php');

// Register autoloader for GCMS classes
Bootstrap::initialize();

// Force registration of custom error handlers before everything elee
class_exists('CustomError');

// Get objects matching the current request
$request = Path::get('request');
$action = Path::get('action');
if ($action == '')
{
	// "show" is the default action, equivalent to not providing an action parameter at all
	$action = 'show';
}

// Get login status and check user credentials if logged in
if (isset($_COOKIE[SESSION_COOKIE]))
{
	User::validate($_COOKIE[SESSION_COOKIE], $timeout);
	if ($timeout && ($action != 'login') && ($action != 'show'))
	{
		// The session was valid, but it timed out. Save session state and redirect to login
		session_start();
		$_SESSION = [
			'refer' => true,
			'data' => $_REQUEST,
			'request' => $_SERVER['PHP_SELF'],
			'action' => $action
		];
		$action = 'login';
	}
}

// Check if site is undergoing maintenance
if ((Settings::get('maintenance') == true)
	&& (User::loggedUser() == '') 
	&& ($action != 'login'))
{
	$action = 'maintenance';
}

// Action dispatcher
//
$flags = [];
switch ($action)
{
	case 'show' :
		list($content, $flags) = Action::show($request);
		break;
	
	case 'edit' :
		list($content, $flags) = Action::edit($request);
		break;
		
	case 'new' :
		$content = Action::newObject();
		break;
		
	case 'store' :
		$content = Action::store($request);
		break;
		
	case 'delete' :
		$content = Action::delete($request);
		break;

	case 'login' :
		$content = Action::login($request);
		break;

	case 'logout' :
		$content = Action::logout($request);
		break;
		
	case 'profile' :
		$content = Action::profile($request);
		break;
	
	case 'maintenance' :
		$content = Content::notify("Maintenance Notice", "This site is currently undergoing maintenance.\n\nPlease visit again later.\n\nWe apologize for the inconvenience caused!");
		break;
		
	default :
		$content = Content::error("Unknown action");
		break;
}

// Render page template
$result = Template::render(Settings::get('siteTheme'), $content, $flags);

// Echo HTML to output
echo $result;

?>