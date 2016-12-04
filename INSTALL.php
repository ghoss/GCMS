<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// INSTALL.php
// One-time installation script. Delete after successful execution!
//
// Created: 26.11.2016 23:19:54 GMT+1
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

require_once('config/gcms.cfg');
require_once(DIR_GCMS . 'Bootstrap.php');

// Register autoloader for GCMS classes
Bootstrap::initialize();

// Check if GCMS database already exists
if (file_exists(DIR_DATA . NAME_DATABASE))
{
	echo "Cannot proceed: GCMS database already exists!";
}
else
{
	$sql = <<<'EOD'
CREATE TABLE object(name text,type text,content text, title text, cdate date,primary key(name));
CREATE INDEX object_cdate on object(cdate);
CREATE TABLE user (username text,password text, sessionid text, exptime datetime, fullname text, timeout integer, primary key(username));
CREATE INDEX session on user(sessionid);
CREATE INDEX password on user(password);
CREATE TABLE media(name text,parent text,draft integer,cdate date, width integer, height integer, primary key(name));
CREATE INDEX media_parent on media(parent);
CREATE TABLE tag(name text collate nocase,objID text);
CREATE INDEX tag_name on tag(name collate nocase);
CREATE INDEX tag_objID on tag(objID);
CREATE TABLE attribute(objID text,name text,value text,primary key(objID,name));
EOD;

	// Setup database tables
	DB::exec($sql);
	
	// Setup primary user
	$user = 'admin';
	$pw = 'admin';
	$hash = hash(AUTH_CIPHER, $user . $pw);
	
	// Create default user
	DB::exec(sprintf(
		'INSERT INTO user VALUES("%s", "%s", "", 0, "%s", %d)', 
		$user, $hash, 'Administrator', 600
	));	

	// Create dummy home page
	DB::exec(sprintf(
		'INSERT INTO object VALUES("home", "text", "Hello World", "Home Page", %d)', 
		time()
	));	

	// Create dummy blog entry
	DB::exec(sprintf(
		'INSERT INTO tag VALUES("blog", "home")'
	));	

	echo "GCMS initialization successful. Admin user = '$user', password '$pw'. Please remove INSTALL.php!";
}

?>