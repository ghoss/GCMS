<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// Parsedown_Ext.php
// Extension of class "Parsedown" to accomodate GCMS specific tags
//
// Created: 19.11.2016 20:23:51 GMT+1
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

require_once(DIR_FRAMEWORK . 'Parsedown.php');

class Markdown extends Parsedown
{
	private static $postName;
	private static $sequence;
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
	// construct()
	// Defines the GCMS custom markup extensions 
	//-----------------------------------------------------------------------------------------

	function __construct()
	{
		$this->InlineTypes['{'][] = 'Tag';
		$this->inlineMarkerList .= '{';
// 		$this->setBreaksEnabled(true);
	}

	
	//-----------------------------------------------------------------------------------------
	// setName
	// Sets the post name (ID) for subsequent reference by inlineTag()
	//-----------------------------------------------------------------------------------------

	public function newPost($name)
	{
		self::$postName = $name;
		self::$sequence = 0;
	}
	
	
	//-----------------------------------------------------------------------------------------
	// inlineTag()
	// Does the actual tag processing and substitution work
	//-----------------------------------------------------------------------------------------

	protected function inlineTag($excerpt)
	{
		if (! preg_match('/^{([^:\s}]+)\s*:\s*([^}]+)?}/', $excerpt['text'], $matches))
			return;
		
		// Tag has form "{TagType:Argument}"
		list($fullstr, $tag, $arg) = $matches;
		$tag = strtolower($tag);
		$result = [];
			
		switch ($tag)
		{	
			case 'thumb' :
			case 'image' :
				$result = array(
					'extent' => strlen($fullstr),
					'element' => [
						'name' => 'img',
						'attributes' => [
							'src' => Settings::get("mediaDir") . '/' . $arg,
							'class' => ($tag == 'image') ?
								'imageNormal zoomableImage' : 
								'imageThumbnail zoomableImage',
							'data-id' => str_replace('.', '', $arg),
							'data-parent' => self::$postName,
							'data-sequence' => self::$sequence ++
						]
					]
				);
				break;
			
			case 'link' :
				// HTML link to internal or external page
				// Split argument into link and description text
				$p = strpos($arg, '|');
				if ($p === false)
				{
					$href = $text = $arg;
					$ext = true;
				}
				else
				{
					$href = ($p > 0) ? substr($arg, 0, $p) : "#";
					$text = substr($arg, $p + 1);
					
					// Check for external vs. internal links
					$ext = (strstr($href, '://') !== false);
				}
				$attr['href'] = $ext ? $href : Settings::get("baseURL") . '/' . $href;
				if ($ext)
				{
					// Open external links in a new window
					$attr += [
						'rel' => 'nofollow',
						'target' => '_blank',
						'class' => 'extlink'
					];
				}
				$result = array(
					'extent' => strlen($fullstr),
					'element' => array(
						'name' => 'a',
						'text' => $text,
						'attributes' => $attr
					)
				);				
				break;
				
			default :
				$result = array(
					'extent' => strlen($fullstr),
					'element' => array(
						'name' => 'b',
						'text' => "Tag Error: '$tag'"
					)
				);
				break;				
		}
		return $result;
	}
}
