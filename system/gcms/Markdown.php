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
	// tag_image()
	// {image:resourceID} and {thumb:resourceID} tags
	//-----------------------------------------------------------------------------------------

	private function tag_image($tag, $arg)
	{
		// Get image ID and optional caption
		$argList = explode('|', $arg);
		$imgID = trim($argList[0]);
		$caption = isset($argList[1]) ? trim($argList[1]) : '';
		
		$imgMarkup = $this->element([
			'name' => 'img',
			'attributes' => [
				'src' => implode('/', [
					Settings::get("domainURL"),
					Settings::get("mediaDir"),
					$imgID
				]),
				'class' => ($tag == 'image') ?
					'imageNormal zoomableImage' : 
					'imageThumbnail zoomableImage',
				'alt' => ($caption != '') ? $caption : $imgID,
				'title' => $caption,
				'data-id' => str_replace('.', '', $imgID),
				'data-parent' => self::$postName,
				'data-sequence' => self::$sequence ++
			]
		]);
		
		// Compose image and associated caption (optional)
		$markup = $imgMarkup;
		if ($caption != '') $markup .= "<em>$caption</em>";
		
		return [
			'markup' => "<div class='imageContainer'>$markup</div>"
		];
	}
	
	
	//-----------------------------------------------------------------------------------------
	// tag_link()
	// {link:url|alttext} tag
	//-----------------------------------------------------------------------------------------
	
	private function tag_link($tag, $arg)
	{
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
			$ext = (strstr($href, '://') !== false)
				|| (strstr($href, 'javascript:') != false);
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
		
		return [
			'element' => [
				'name' => 'a',
				'text' => $text,
				'attributes' => $attr
			]
		];				
	}
	

	//-----------------------------------------------------------------------------------------
	// tag_map()
	// {map:lat,long,zoom} OpenTopoMap integration
	//-----------------------------------------------------------------------------------------

	private function tag_map($tag, $arg)
	{
		// Determine map coordinates
		list($lat, $long, $zoom) = explode(',', $arg);
		return [
			'element' => [
				'name' => 'script',
				'text' => sprintf("mapDisplay('%s',%s,%s,%s);", 
					self::$postName, $lat, $long, $zoom)
			]
		];				
	}
	
	
	//-----------------------------------------------------------------------------------------
	// tag_marker()
	// {marker:lat,long,text} OpenTopoMap waypoint marker
	// Must occur after map definition
	//-----------------------------------------------------------------------------------------

	private function tag_marker($tag, $arg)
	{
		// Determine map coordinates
		list($lat, $long, $text) = explode(',', $arg);
		return [
			'element' => [
				'name' => 'script',
				'text' => sprintf("mapMarker(%s,%s,'%s');", $lat, $long, $text)
			]
		];				
	}
	
	
	//-----------------------------------------------------------------------------------------
	// tag_gpx()
	// {gpx:resourceID} OpenTopoMap GPX track overlay
	// Must occur after map definition
	//-----------------------------------------------------------------------------------------

	private function tag_gpx($tag, $arg)
	{
		return [
			'element' => [
				'name' => 'script',
				'text' => sprintf("mapTrack('%s')", Settings::get("mediaDir") . '/' . $arg)
			]
		];						
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
				// Image or thumbnail
				$result = self::tag_image($tag, $arg);				
				break;
			
			case 'link' :
				// HTML link to internal or external page
				$result = self::tag_link($tag, $arg);				
				break;
			
			case 'map' :
				// OpenTopoMap window
				$result = self::tag_map($tag, $arg);	
				break;			
				
			case 'marker' :
				// OpenTopoMap window
				$result = self::tag_marker($tag, $arg);	
				break;			
				
			case 'gpx' :
				// OpenTopoMap GPX track overlay
				$result = self::tag_gpx($tag, $arg);
				break;

			default :
				$result = [
					'element' => [
						'name' => 'b',
						'text' => "Tag Error: '$tag'"
					]
				];
				break;				
		}
		
		$result['extent'] = strlen($fullstr);
		return $result;
	}
}
