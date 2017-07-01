<?php
//=============================================================================================
// GCMS - GUIDO'S CONTENT MANAGEMENT SYSTEM
//=============================================================================================
// Template.php
// Methods to parse HTML templates
//
// Created: 17.08.2016 00:00:56 GMT+2
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

class Template
{	
	private static $instance;
	private static $data;
	private static $meta;
	
	
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
	// clearMeta()
	// Clear HTML meta data
	//-----------------------------------------------------------------------------------------
	
	public static function clearMeta()
	{
		self::$meta = [];	
	}
	
	
	//-----------------------------------------------------------------------------------------
	// setMeta()
	// Set HTML meta data, if it hasn't been set already since last call to clearMeta()
	//
	// prop :		Name of meta property to set
	// attrib :		Content assigned to property
	//-----------------------------------------------------------------------------------------
	
	public static function setMeta($element)
	{
		// Get name of tag (first key in element array)
		reset($element);
		$tagKey = key($element);
		
		// Get content of tag (second key in element array)
		end($element);
		$valKey = key($element);
		
		if (! isset(self::$meta[$element[$tagKey]]))
		{
			self::$meta[$element[$tagKey]] = [$tagKey, $valKey, $element[$valKey]];
		}
	}
	
	
	//-----------------------------------------------------------------------------------------
	// metaFromPost()
	// Determine and set meta data from specified post
	//
	// post :		Post to process
	//-----------------------------------------------------------------------------------------
	
	public static function metaFromPost($post)
	{
		// Robot search flag
		if (isset($post['attributes']['nr']))
		{
			self::setMeta([
				'name' => 'ROBOTS', 
				'content' => 'NOINDEX, FOLLOW'
			]);
		}
		
		// Facebook Open Graph
		if (isset($post['attributes']['ft']))
		{
			// Get dimensions of featured image from database
			$featuredImg = $post['attributes']['ft'];
			$row = DB::query(
				sprintf('SELECT width,height FROM media WHERE name="%s"', $featuredImg), 
				true, true
			);
			if (! empty($row))
			{			
				self::setMeta([
					'property' => 'og:title',
					'content' => $post["title"]
				]);
				self::setMeta([
					'property' => 'og:type',
					'content' => 'article'
				]);
				self::setMeta([
					'property' => 'og:url',
					'content' => Settings::get("baseURL"). "/" .$post["name"]
				]);
				self::setMeta([
					'property' => 'og:image',
					'content' => implode('/', [
						Settings::get('domainURL'), 
						Settings::get('mediaDir'),
						$featuredImg
					])
				]);
				self::setMeta([
					'property' => 'og:image:width',
					'content' => $row['width']
				]);
				self::setMeta([
					'property' => 'og:image:height',
					'content' => $row['height']
				]);
			}
		}
	}
	
	
	//-----------------------------------------------------------------------------------------
	// renderMeta()
	// Renders meta data in HTML format
	//
	// Returns a string with HTML code.
	//-----------------------------------------------------------------------------------------
	
	public static function renderMeta()
	{
		$output = '';
		foreach (self::$meta as $name => $content)
		{
			list($tagKey, $valKey, $val) = $content;
			$output .= "<meta $tagKey='$name' $valKey='$val' />";
		}
		return $output;
	}
	
	
	//-----------------------------------------------------------------------------------------
	// compile()
	// Compile a source template
	//
	// tpl :	Path to source template
	// tplc : Path to destination template
	//-----------------------------------------------------------------------------------------
	
	public static function compile($tpl, $tplc)
	{
		// Get contents of source template
		$source = file_get_contents($tpl);
		if ($source === false)
		{
			trigger_error(sprintf(_("Can't read template '%s'"), $tpl));
		}
		
		// Find meta fields {...}
		$output = preg_replace_callback(
			"#\{([a-zA-Z/][^\}]+)\}#",
			'Template::subst_fields',
			$source
		);
		
		// Compress output
		$output = self::compressHTML($output);
		
		// Write compiled template to cache
		$res = file_put_contents($tplc, $output);
		if ($res === false)
		{
			trigger_error(sprintf(_("Can't write compiled template '%s'"), $tplc));
		}
	}
	
	
	private static function subst_fields($match)
	{
		$var = true;
		
		switch ($match[1])
		{
			case 'block:Posts' :
				$res = 'foreach (Template::get("content") as $post): $post["tags"] = array_diff($post["tags"], ["untagged"])';
				$var = false;
				break;
				
			case 'block:Tags' :
				$res = 'foreach ($post["tags"] as $tag):';
				$var = false;
				break;

			case '/block:Posts' :
			case '/block:Tags' :
				$res = "endforeach;";
				$var = false;
				break;
			
			case 'if:LoggedUser' :
				$res = 'if (User::loggedUser() != ""):';
				$var = false;
				break;
				
			case 'if:Pagination' :
				$res = 'if (isset(Template::get("flags")["pagination"])):';
				$var = false;
				break;
				
			case 'if:SinglePost' :
				$res = 'if (isset(Template::get("flags")["singlePost"])):';
				$var = false;
				break;
				
			case 'if:NextPage' :
				$res = 'if (isset(Template::get("flags")["nextPage"])):';
				$var = false;
				break;

			case 'if:PrevPage' :
				$res = 'if (isset(Template::get("flags")["prevPage"])):';
				$var = false;
				break;

			case 'if:EditMode' :
				$res = 'if (isset(Template::get("flags")["editmode"])):';
				$var = false;
				break;
				
			case 'if:Editable' :
				$res = 'if (isset(Template::get("flags")["acl_edit"])):';
				$var = false;
				break;

			case 'if:PostDate' :
				$res = 'if (! isset($post["attributes"]["hd"])):';
				$var = false;
				break;

			case 'if:Private' :
				$res = 'if (isset($post["attributes"]["pv"])):';
				$var = false;
				break;

			case 'if:Tags' :
				$res = 'if (! isset($post["attributes"]["ht"])):';
				$var = false;
				break;

			case 'if:NoRobots' :
				$res = 'if (isset($post["attributes"]["nr"])):';
				$var = false;
				break;

			case 'if:Comments' :
				$res = 'if (! isset($post["attributes"]["nc"])):';
				$var = false;
				break;

			case 'if:Sticky' :
				$res = 'if (isset($post["attributes"]["st"])):';
				$var = false;
				break;

			case 'if:Debug' :
				$res = 'if (Settings::get("debug")):';
				$var = false;
				break;

			case 'else' :
				$res = 'else:';
				$var = false;
				break;
				
			case '/if:SinglePost' :
			case '/if:LoggedUser' :
			case '/if:Pagination' :
			case '/if:NextPage' :
			case '/if:PrevPage' :
			case '/if:Editable' :
			case '/if:EditMode' :
			case '/if:PostDate' :
			case '/if:Private' :
			case '/if:Tags' :
			case '/if:NoRobots' :
			case '/if:Comments' :
			case '/if:Sticky' :
			case '/if:Debug' :
				$res = 'endif;';
				$var = false;
				break;

			case 'Meta' :
				$res = 'Template::renderMeta()';
				break;
				
			case 'Title' :
				$res = '$post["title"]';
				break;

			case 'Body' :
				$res = 'Template::format($post)';
				break;
				
			case 'Markup' :
				$res = '$post["content"]';
				break;
				
			case 'Tag' :
				$res = '$tag';
				break;
				
			case 'TagList' :
				$res = 'implode(", ", $post["tags"])';
				break;

			case 'PostDate' :
				$res = 'strftime("%d.%m.%Y", $post["cdate"])';
				break;

			case 'PostTime' :
				$res = 'strftime("%H:%M:%S", $post["cdate"])';
				break;

			case 'Site' :
				$res = "Path::get('site')";
				break;
				
			case 'UserName' :
				$res = "User::loggedUser()";
				break;
				
			case 'AssetURL' :
				$res = 'Settings::get("assetDir")';
				break;
				
			case 'SiteAssetURL' :
				$res = 'Settings::get("siteAssetDir")';
				break;

			case 'MediaURL' :
				$res = 'Settings::get("mediaDir")';
				break;

			case 'BaseURL' :
				$res = 'Settings::get("baseURL")';
				break;

			case 'BaseDir' :
				$res = 'Settings::get("baseDir")';
				break;
				
			case 'Permalink' :
				$res = 'Settings::get("baseURL")."/".$post["name"]';
				break;
			
			case 'ObjectID' :
				$res = '$post["name"]';
				break;
			
			case 'Request' :
				$res = 'Settings::get("baseURL")."/".implode("/",Path::get("request"))';
				break;
			
			case 'NextPage' :
				$res = 'Template::get("flags")["nextPage"]';
				break;
				
			case 'PrevPage' :
				$res = 'Template::get("flags")["prevPage"]';
				break;
			
			case 'NumPages' :
				$res = 'Template::get("flags")["numPages"]';
				break;
			
			case 'ThisPage' :
				$res = 'Template::get("flags")["thisPage"]';
				break;
			
			case 'FeaturedImage' :
				$res = 'isset($post["attributes"]["ft"]) ? $post["attributes"]["ft"] : ""';
				break;
				
			case 'Timeout' :
				$res = 'User::get("timeout")';
				break;
				
			case 'Version' :
				$res = "Settings::get('version')";
				break;
				
			default :
				// Unmatched tag
				$res = "'??$match[1]??'";
				break;
		}
		return $var ? "<?=$res?>" : "<?php $res ?>";
	}
	
	
	//-----------------------------------------------------------------------------------------
	// format()
	// Produce a formatted version of the record's content
	//
	// post :	Post to be formatted
	//-----------------------------------------------------------------------------------------
	
	public static function format($post)
	{
		$content = $post['content'];
		$type = $post['type'];
		
		switch ($type)
		{
			case 'text' :
				// Produce markdown
				$md = new Markdown();
				$md->newPost($post['name']);
				$result = $md->text($content);
				
				if (isset(self::$data['flags']['singlePost']))
				{
					// Suppress "read more" break in single posts
					$result = str_ireplace('<hr />', '', $result);
				}
				else
				{
					// Replace "read more" break by permalink to full article in multi posts
					$permalink = Settings::get("baseURL")."/".$post["name"];
					$result = preg_replace(
						'/<hr \/>.*/ms', 
						"<a href='$permalink'>" . _("Continue Reading") . "…</a>",
						$result
					);
				}
				break;
				
			case 'html' :
				// Do nothing
				$result = $content;
				break;
			
			default :
				trigger_error(sprintf(_("Unrecognized object type '%s'"), $type));
				break;
		}
		return $result;
	}
	
	
	//-----------------------------------------------------------------------------------------
	// compressHTML()
	// Helper function to remove whitespace from HTML before outputting it
	//
	// buffer :		HTML code to compress
	//-----------------------------------------------------------------------------------------
	
	private static function compressHTML($buffer)
	{
        $buffer = preg_replace('~>\s*\n\s*<~', '><', $buffer); 
		return trim($buffer);
	}
	
	
	//-----------------------------------------------------------------------------------------
	// render()
	// Render the specified template
	//
	// name :		Name of template
	// content :	Post content to be filled into template
	// flags :		Flags for content
	//-----------------------------------------------------------------------------------------
	
	public static function render($tpl, $content = array(), $flags = array())
	{
		// Get paths of source and compiled templates for current site
		$tplc = Settings::get("ctplDir") . $tpl;
		$tpl = Settings::get("tplDir") . $tpl;
		
		// Check if source template has been modified
// FOR DEBUGGING / DEVELOPMENT
// 		if (true)
		if ((! file_exists($tplc)) || (filemtime($tplc) < filemtime($tpl)))
		{
			// Compile modified source
			self::compile($tpl, $tplc);
		}

		// Determine page meta data
		self::clearMeta();
		foreach ($content as $post)
		{
			self::metaFromPost($post);
		}

		// Execute compiled template
		ob_start();
		self::$data['content'] = $content;
		self::$data['count'] = count($content);
		self::$data['flags'] = $flags;
		include $tplc;
		return ob_get_clean();
	}

		
	//-----------------------------------------------------------------------------------------
	// redirect()
	// Redirect to the specified page name and append the supplied action
	//
	// name :	Page name to be redirected to
	// action :	Action to be specified
	//-----------------------------------------------------------------------------------------

	public static function redirect($name, $action)
	{
		$url = sprintf("%s/%s?action=%s", Settings::get('baseURL'), $name, $action);
		header('Location: ' . $url, true);
		exit();
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