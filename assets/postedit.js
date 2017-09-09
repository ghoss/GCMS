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
//
// Compression command line: uglifyjs -c -m -o postedit.min.js postedit.js


// Event handler for image upload field
// jQuery initialization
$(document).ready(function() {
	// Add event handler to thumbnail upload field
	document.getElementById('postUpload').addEventListener('change', handleFileSelect, false);
	
	// Preload images already assigned to post
	getMediaList($('#objectId').val());
	
	// Zoomed images disappear when ESC key pressed
	$(document).keyup(function(e){
		if(e.keyCode === 27) closeZoom();
	});
	
	// Remove navigation bars on zoomed image display
	$('.imgNav').addClass('nodisplay');
	
	// Activate context menu for thumbnails
	bindImageMenu();
});	


function isImage(ext)
{
	return (ext == 'png') || (ext == 'jpg') || (ext == 'gif');
}


function handleFileSelect(evt) {

	// for each entry, add to formdata to later access via $_FILES["file" + i]
	var files = evt.target.files; // FileList object
	var objectId = $('#objectId').val();
	
	// Loop through all upload files
	for (var i = 0, f; f = files[i]; i ++)
	{
		var formData = new FormData();
		formData.append(i, f);
		formData.append("objectId", objectId);
		formData.append("seq", i);
		
		var filename = escape(f.name);
		
		// Send one file at a time
		$.ajax({
			url: basedir + "/media.php?action=upload", // our php file
			type: 'post',
			data: formData,
			dataType: 'json', // we return html from our php file
			processData: false,  // tell jQuery not to process the data
			contentType: false,   // tell jQuery not to set contentType
			success : function(data)
			{
				if (data.success == 1)
				{
					// Render thumbnail
					var filename = escape(data.msg[0]['name']);
					var ext = data.msg[0]['ext'];
					var seq = parseInt(data.msg[0]['seq']);
					var isImg = isImage(ext);
					var reader = new FileReader();
					reader.onload = (function(theFile)
					{
						return function(e)
						{
							// Render thumbnail
							var img = createThumbnail(e.target.result,
								filename, ext, seq, isImg);
								
							if (($('.featuredImg').length == 0) && isImg)
							{
								setFeatured($(img));
							}
						};
					})(f);

					// Read in the image file as a data URL.
					reader.readAsDataURL(files[data.msg[0]['index']]);
				}
				else
				{
					alert("An error occured during the upload of '" + filename + 
						"':\n" + data.msg);
				}
			},
			error : function(request)
			{
				alert("An error occured during the upload of '" + filename + "'");
				console.log(request.responseText);
			}
		});
	}
}


function setFeatured(target)
{
	$('.uploadThumbnail').removeClass('featuredImg');
	if (target.length == 1)
	{
		target.addClass('featuredImg');
		$('#featured').val(target.attr('title'));
	}
	else
	{
		$('#featured').val('');
	}
}


function deleteMedia(name)
{
	$.ajax({
		url: basedir + "/media.php?action=delete&id=" + name,
		type: 'get',
		dataType: 'json',
		processData: false,  // tell jQuery not to process the data
		contentType: false,   // tell jQuery not to set contentType
		success : function(data)
		{
			if (data.success == 1)
			{
				// Remove thumbnail
				var name = data.msg;
				$('#' + name.replace('.', '')).remove();
				
				// Reset featured image to first image in gallery, if current one was deleted
				if (name == $('#featured').val())
				{
					setFeatured($('#postMedia .uploadThumbnail').first());
				}
			}
			else
			{
				alert("An error occured during the deletion of '" + name + 
					"':\n" + data.msg);
			}
		},
		error : function(request)
		{
			alert("An error occured during the deletion of '" + name + "'");
			console.log(request.responseText);
		}
	});
	return false;
}


function bindImageMenu()
{
	// Highlight menu options when hovering over them
	var menuItems = $('#imageMenu li');
	
    menuItems.hover(
    	function(e) {
    		$(this).addClass('highlight');
    		e.stopPropagation();
    	},
    	function(e) {
     		$(this).removeClass('highlight');
   			e.stopPropagation();
    	}
    );
    
    // Click handler for individual menu items
    menuItems.click(function() {
    	// Remove context menu after item has been clicked
    	$('#imageMenu').addClass('nodisplay');
    	
    	// Execute corresponding menu action
    	imageMenuAction(this);
    });
    
    // Remove the menu after the cursor has left its focus
    $('#imageMenu').hover(
    	function() {
    		$('#imageMenu').removeClass('nodisplay');
    	},
    	function() {
    		$('#imageMenu').addClass('nodisplay');
    	}
    );
    
    // Bind the menu to all upload image thumbnails
	$('body').on('mousedown', '.uploadThumbnail', function(e) {
		switch (e.which)
		{
			case 1 :
				// Left mouse button: insert link to image into post
// 				insertTxt('Thumb', $(e.target).attr('title'));
				// Left mouse button: zoom thumbnail
				zoomImage($(e.target));
				break;
				
			case 2 :
				// Middle mouse button: ignore
				break;
				
			case 3 :
				// Right mouse button; show context menu
    			imageMenuHandler(e, true);
				break;
		}	
    });
    
    // Bind the menu to any remaining attachment icons, but disable zoom function
	$('body').on('mousedown', '.uploadIcon', function(e) {
		switch (e.which)
		{
			case 1 :	
			case 2 :
				// Middle mouse button: ignore
				break;
				
			case 3 :
				// Right mouse button; show context menu
    			imageMenuHandler(e, true);
				break;
		}	
    });

	// Override browser context menu in thumbnail upload box
	$('#uploadBox').contextmenu(function() {
		return false;
	});
}


function imageMenuHandler(event, state)
{
    	if (state)
		{
			var menu = $('#imageMenu');
			var isImg = isImage($(event.target).data('ext'));
			menu.data('target', event.target);

			// Disable non-image menu options
			['thumb', 'image', 'feature'].forEach(function(el, idx, arr)
			{
				var tag = '[data-action="' + el + '"]';
				if (isImg)
				{
					menu.find(tag).removeClass('nodisplay');
				}
				else
				{
					menu.find(tag).addClass('nodisplay');
				}
			});

			menu.css({
				'top' : event.pageY - menu.height() / 2,
				'left': event.pageX
			});
    		menu.removeClass('nodisplay');
    	}
    	else
    	{
    		$('#imageMenu').addClass('nodisplay');
    	}
}


function imageMenuAction(obj)
{
	var target = $('#imageMenu').data('target');
	var name = $(target).attr('title');
	
	switch ($(obj).data('action'))
	{
		case 'thumb' :
			// Insert link to thumbnail in post content
			insertTxt('Thumb', name);
			break;
			
		case 'image' :
			// Insert link to image in post content
			insertTxt('Image', name);
			break;
			
		case 'link' :
			// Insert link to file
			insertTxt('Attach', name);
			break;
		
		case 'feature' :
			// Make image featured
			setFeatured($(target));
			break;

		case 'delete' :
			deleteMedia(name);
			break;
	}
}


function getMediaList(objectId)
{
	// Get list of media associated with objectId
	$.ajax({
		url: basedir + "/media.php?action=getlist&id=" + objectId, // our php file
		type: 'get',
		dataType: 'json', // we return html from our php file
		processData: false,  // tell jQuery not to process the data
		contentType: false,   // tell jQuery not to set contentType
		success : function(data)
		{
			if (data.success == 1)
			{
				// Render thumbnail
				var featured = $('#featured').val();
				for (i = 0; i < data.msg.length; i ++)
				{
					var row = data.msg[i]
					var filename = escape(row.name);
					var ext = row.ext;
					var isImg = isImage(ext);
					var img = createThumbnail(mediadir + '/' + filename, 
						filename, ext, i, isImg);
					if (filename == featured) img.addClass('featuredImg');
				}
			}
			else
			{
				alert("An error occured while getting the media list:\n" + data.msg);
			}
		},
		error : function(request)
		{
			alert("An error occured while getting the media list:\n" + request.responseText);
		}
	});
}


function createThumbnail(imgPath, imgName, ext, seq, isImage)
{
	if (isImage)
	{
		var template = $('#sampleThumbnail').clone();
		var img = template.children('img');
	
		template.attr('id', imgName.replace('.', ''));
		img.attr('src', imgPath);
	}
	else
	{
		var template = $('#sampleIcon').clone();
		var img = template.children('div');
	
		template.attr('id', imgName.replace('.', ''));
		$(img).html('&nbsp;<br />' + ext);
	}
	
	var children = $('#postMedia').children();
	var numElem = children.length;
	
	template.attr('seq', seq);
	// Insert thumbnail in correct order
	if (numElem == 0)
	{
		// First element in thumbnail list
		template.appendTo('#postMedia');
	}
	else if (seq < parseInt(children.first().attr('seq')))
	{
		// Prepend element to beginning of list
		template.prependTo('#postMedia');
	}
	else
	{
		// Find correct insertion point in sorted list of previous thumbnails
		var node = children.last();
		while (parseInt(node.attr('seq')) > seq)
		{
			node = node.prev();
		}
		template.insertAfter(node);
	}
	
	// Add some extra fields and display thumbnail
	img.attr('title', imgName);
	img.attr('data-ext', ext);
	template.removeClass('nodisplay');
	return img;
}


function insertTxt(tag, obj)
{
	var txtToAdd = '{' + tag + ': ' + obj + '}';
	var txt = document.getElementById('postContent');
	var caretStartPos = txt.selectionStart,
		caretEndPos = txt.selectionEnd,
		textAreaTxt = txt.value;
		
	txt.value = textAreaTxt.substring(0, caretStartPos) 
		+ txtToAdd 
		+ textAreaTxt.substring(caretEndPos);

	txt.focus();
	txt.selectionStart = caretStartPos + txtToAdd.length;
	txt.selectionEnd = txt.selectionStart;
	return false;
}


function removeUploads()
{
	// Remove upload file fields from DOM before form submission since files have already
	// been uploaded via AJAX
	$('#postUpload').remove();
	return true;
}


function closeZoom()
{
	$('#backdrop').addClass('nodisplay');
	return false;
}


function zoomImageHandler()
{
	zoomImage($(this));
}


function zoomImage(thisImg)
{
	var imgBox = $('#imageBoxSrc');
	
	// Set image source URL
	imgBox.attr('src', thisImg.attr('src'));

	$('#backdrop').removeClass('nodisplay');
	return false;
}