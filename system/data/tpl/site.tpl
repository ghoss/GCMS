<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<title>{Site}</title>	
	{Meta}
	<base href="{BaseDir}/" />
	<link rel="stylesheet" type="text/css" href="{AssetURL}/site{if:Debug}{else}.min{/if:Debug}.css" />
	{if:Pagination}
		{if:PrevPage}
			<link rel="prev" href="{Request}?page={PrevPage}">
		{/if:PrevPage}
		{if:NextPage}
			<link rel="next" href="{Request}?page={NextPage}">
		{/if:NextPage}
	{/if:Pagination}
	<script defer type="text/javascript" src="{AssetURL}/jquery.min.js"></script>
	{if:EditMode}
		<script>
			var basedir = '{BaseDir}';
			var mediadir = '{MediaURL}';
			var assetdir = '{AssetURL}';
		</script>
		<script defer type="text/javascript" src="{AssetURL}/postedit{if:Debug}{else}.min{/if:Debug}.js"></script>
	{else}
		<script>
			{if:Pagination}
				var numPages = {NumPages};
				var thisPage = {ThisPage};
				var request = '{Request}';
			{else}
				var numPages = 0;
				var thisPage = 0;
			{/if:Pagination} 
		</script>
		<script defer type="text/javascript" src="{AssetURL}/postshow{if:Debug}{else}.min{/if:Debug}.js"></script>	
	{/if:EditMode}
</head>
<body>
    <div id="contentContainer">
    <div id="header">
        <div class="hdrTitle"><a href="{BaseURL}">{Site}</a></div>
    	<div class="hdrDescr">Sample CMS Layout</div>
   </div>
    <div id="content" class="floatLeft">
		<div id='backdrop' class='nodisplay'>
			<div id='imageBox'>
				<img id='imageBoxSrc' src='' />
				<div><a id='closeBox' href='#' onclick='return closeZoom()'>Close (or Esc)</a></div>
			</div>
			<a href='#' id='imgNavLeft' class='imgNav' onclick='return zoomImage($(this).data("goto"))'>&#10094;</a>
			<a href='#' id='imgNavRight' class='imgNav' onclick='return zoomImage($(this).data("goto"))'>&#10095;</a>
		</div>    	
		{if:Pagination}
    		<div class="pageNav">
				{if:PrevPage}
					<a href="{Request}?page={PrevPage}">&#8672; Previous Page</a>
				{else}
					<span class="gray">&#8672; Previous Page</span>
				{/if:PrevPage}
				<span class='pageBar'></span>
				{if:NextPage}
					<a href="{Request}?page={NextPage}">Next Page &#8674;</a>
				{else}
					<span class="gray">Next Page &#8674;</span>
				{/if:NextPage}
			</div>
    	{/if:Pagination} 

		{block:Posts}
			<div class="article">
				{if:EditMode}
					<form class='textBody' method='post' action='{Permalink}?action=store' enctype='multipart/form-data' onsubmit='return removeUploads()'>
						<input type='hidden' name='objectId' id='objectId' value='{ObjectID}' />
						<input type='hidden' name='attr[ft]' id='featured' value='{FeaturedImage}' />
						<div class='postDate'>
							<div class='floatLeft'>
								Post Date: <input type='text' name='postDate' value='{PostDate} {PostTime}' />
							</div>
							<div class='floatRight'>Editing: {Permalink}</div>
							<div class='floatNone'></div>
						</div>
						<h2 class='textTitle'>
							<input type='text' name='postTitle' placeholder='Title' value='{Title}' autofocus />
						</h2>
						<div><textarea id='postContent' name='postContent' placeholder='Content' style='height:200px'>{Markup}</textarea></div>
						<div class='editsection'>Attributes:</div>
						<div>
							<input type='checkbox' name='attr[pv]' value='1' {if:Private}checked{/if:Private} /> Hide post from public
							<br /><input type='checkbox' name='attr[hd]' value='1' {if:PostDate}{else}checked{/if:PostDate} /> Hide Date
							<br /><input type='checkbox' name='attr[ht]' value='1' {if:Tags}{else}checked{/if:Tags} /> Hide Tags
							<br /><input type='checkbox' name='attr[nr]' value='1' {if:NoRobots}checked{else}{/if:NoRobots} /> Hide From Robots
							<br /><input type='checkbox' name='attr[nc]' value='1' {if:Comments}{else}checked{/if:Comments} /> Disable Comments & Social Plug-Ins
							<br /><input type='checkbox' name='attr[st]' value='1' {if:Sticky}checked{/if:Sticky} /> Make Sticky Post
						</div>
						<div class='editsection'>Tagged with:</div>
						<div><textarea id='postTags' name='postTags' placeholder='Tags (comma separated)' style='height:50px'>{TagList}</textarea></div>
						<div class='editsection'>Upload Media:</div>
						<div class='addBorder'><input type="file" id="postUpload" name="postFiles[]" multiple /></div>
						<div class='editsection'>Currently Attached Media <span class='small'>(right-click on an image for options)</span>:</div>
						<div class="addBorder" id="uploadBox">
							<div id='imageMenu' class='nodisplay'>
								<ul>
									<li data-action='thumb'>Insert Link to Thumbnail</li>
									<li data-action='image'>Insert Link to Image</li>
									<li data-action='feature'>Make Featured</li>
									<li data-action='delete'>Delete Image</li>
								</ul>
							</div>
							<span id='sampleThumbnail' class='nodisplay'>
								<img class='uploadThumbnail' />
							</span>
							<output id="postMedia"></output>&nbsp;
						</div>
						<div class="floatNone">&nbsp;<br /><input type="submit" value="Submit Changes" /> <input type="button" value="Cancel" onclick="window.history.back()" /></div>
					</form>
				{else}
					{if:Editable}
					<div class='postDate'>
						{if:PostDate}
						<div class='articleDate floatLeft'>{PostDate}</div>
						{/if:PostDate}
						<div class='articleDate floatRight'>
							<a href="{Permalink}?action=edit">Edit</a> | <a href="{Permalink}?action=delete" onclick="return delConfirm()">Delete</a>
						</div>
						<div class='floatNone'></div>
					</div>
					{else}
						{if:PostDate}
						<div class='postDate'>
							<div class='articleDate floatLeft'>{PostDate}</div>
							<div class='floatNone'></div>
						</div>
						{/if:PostDate}
					{/if:Editable}
				
					<h2 class='textTitle'>
						{if:Private}[Private] {/if:Private}<a href="{Permalink}">{Title}</a>
					</h2>
					<div class='textBody'>
						{Body}
					</div>
					{if:Tags}
					<div class='tagList'>
						<ul>
						{block:Tags}
							<li><a href="{BaseURL}/{Tag}">{Tag}</a></li>
						{/block:Tags}
						</ul>
					</div>
					{/if:Tags}				
				{/if:EditMode}
			</div> <!-- article -->
		{/block:Posts}

    	{if:Pagination}
		<div class="pageNav">
			{if:PrevPage}
				<a href="{Request}?page={PrevPage}">&#8672; Previous Page</a>
			{else}
				<span class="gray">&#8672; Previous Page</span>
			{/if:PrevPage}
			<span class='pageBar'></span>
			{if:NextPage}
				<a href="{Request}?page={NextPage}">Next Page &#8674;</a>
			{else}
				<span class="gray">Next Page &#8674;</span>
			{/if:NextPage}
		</div>
    	{/if:SinglePost} 
	</div> <!-- content -->
	
	<div class="floatRight">
    	<div class="menu">
    		<ul>
    		    <li><a href="{BaseURL}">Home</a></li>
				{if:LoggedUser}
					<li><ul>
						<li><a href="{BaseURL}?action=logout">Logout [{UserName}]</a></li>
						<li><a href="{BaseURL}?action=profile">Profile Settings</a></li>
						<li><a href="{BaseURL}?action=new&tags=blog">New Page</a></li>
					</ul></li>
				{/if:LoggedUser}
    		    <li><a href="{BaseURL}/blog">Blog</a></li>
    		</ul>
    	</div> <!--menu -->
    </div><!-- menu and affiliate -->
    <div class="floatNone"></div>
	</div> <!-- contentContainer -->
	
	<div id="footer">
		<p>
			CMS Software: <a href="https://github.com/ghoss/GCMS">{Version}</a>
			{if:LoggedUser}{else}
				- <a href="{BaseURL}?action=login" rel="nofollow">Login</a>
			{/if:LoggedUser}
		</p>
	</div>  <!-- footer -->
</body>
</html>
