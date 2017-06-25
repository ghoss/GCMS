<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// UPGRADE-1_0-1_1.php
// One-time upgrade script. Delete after successful execution!
// This script upgrades an installed GCMS version 1.0.x to 1.1.
//
// Created: 23.06.2017 18:56:58 GMT+2
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

// Check if GCMS database already exists
if (! file_exists(DIR_DATA . NAME_DATABASE))
{
	echo "Cannot proceed: GCMS database does not exist!";
}
else
{
	// Update DB schema to include siteID in various tables
	$siteID = Settings::get('siteID');

	$sql = <<<EOD
/* Update table "object" */
DROP INDEX object_cdate;
CREATE TABLE new_object(name text, type text,content text, title text, cdate date, siteID integer, primary key(name,siteID));	
INSERT INTO new_object SELECT *,$siteID AS siteID FROM object;
DROP TABLE object;
ALTER TABLE new_object RENAME TO object;
CREATE INDEX object_cdate on object(cdate,siteID);
/* Update table "tag" */
DROP INDEX tag_name;
DROP INDEX tag_objID;
ALTER TABLE tag ADD COLUMN siteID integer;
UPDATE tag SET siteID=$siteID;
CREATE INDEX tag_name on tag(name collate nocase,siteID);
CREATE INDEX tag_objID on tag(objID,siteID);
/* Update table "attribute" */
CREATE TABLE new_attribute(objID text,name text,value text,siteID integer,primary key(objID,siteID,name));
INSERT INTO new_attribute SELECT *,$siteID AS siteID FROM attribute;
DROP TABLE attribute;
ALTER TABLE new_attribute RENAME TO attribute;
/* Update table "media" */
DROP INDEX media_parent;
CREATE TABLE new_media(name text,parent text,draft integer,cdate date, width integer, height integer, siteID integer, primary key(name,siteID));
INSERT INTO new_media SELECT *,$siteID AS siteID FROM media;
DROP TABLE media;
ALTER TABLE new_media RENAME TO media;
CREATE INDEX media_parent on media(parent,siteID);
/* Create table "session" */
DROP INDEX session;
CREATE TABLE session (username text,sessionID text,exptime datetime,primary key(sessionID));
/* Update table "user" */
DROP INDEX password;
CREATE TABLE new_user (username text,password text, fullname text, timeout integer, primary key(username));
INSERT INTO new_user SELECT username,password,fullname,timeout FROM user;
DROP TABLE user;
ALTER TABLE new_user RENAME TO user;
CREATE INDEX user_password on user(password);
EOD;

	// Update database tables
	DB::exec($sql);
	
	// Setup primary user
	$siteID = Settings::get('siteID');
	
// 	DB::exec(sprintf(
// 		'INSERT INTO user VALUES("%s", "%s", "", 0, "%s", %d)', 
// 		$user, $hash, 'Administrator', 600
// 	));	

	echo "GCMS update successful. Please remove UPGRADE-1_0-1_1.php!";
}

?>