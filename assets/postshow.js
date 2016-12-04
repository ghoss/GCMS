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


var slideShow = [];

// jQuery initialization
$(function() {
	// Zoomed images disappear when ESC key pressed
	$(document).keyup(function(e){
		if(e.keyCode === 27) closeZoom();
	});
	
	// Thumbnails zoom when clicked on
	$('.zoomableImage').click(zoomImageHandler);
	
	// Make page bar
	if (numPages != 0) makePageBar(numPages, thisPage);
	
	// Load Facebook SDK for sharing
	window.fbAsyncInit = function() {FB.init({
		appId      : '1789642194635204',
		xfbml      : true,
		version    : 'v2.8'
	})};

	(function(d, s, id){
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement(s); js.id = id;
		js.src = "//connect.facebook.net/en_US/sdk.js";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));
});	


function delConfirm()
{
	return confirm('Do you really want to delete this entry?');
}


function selector(parent, idx)
{
	return ".zoomableImage[data-parent='" + parent + "'][data-sequence='" + idx + "']";
}


function makePageBar(numPages, thisPage)
{
	var res = [];
	var startp = 1;
	var endp = numPages;
	
	function addPage(n)
	{
		if (n != 0)
		{
			// Current page is bold
			var p = (n == thisPage) ? '<b>' + n + '</b>' : n;
			res.push('<a href="' + request + '?page=' + n + '">' + p + '</a>');
		}
		else
		{
			res.push('&hellip;');
		}
	}
	
	if (thisPage > 4)
	{
		startp = thisPage - 2;
		addPage(1);
		addPage(0);
	}
	if (thisPage < numPages - 4)
	{
		endp = thisPage + 2;
	}	
	for (var i = startp; i <= endp; i ++)
	{
		addPage(i);
	}
	if (thisPage < numPages - 4)
	{
		addPage(0);
		addPage(numPages);
	}	
	
	// Assign resulting string to page bar elements
	$('.pageBar').html(res.join(' &bull; '));
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
	
	// Check for previous and next images in slideshow sequence
	var parent = thisImg.data('parent'); 
	var sequence = thisImg.data('sequence'); 
	var prev = $(selector(parent, sequence - 1));
	var next = $(selector(parent, sequence + 1));
	
	$('.imgNav').addClass('nodisplay');
	if (next.length)
	{
		var obj = $('#imgNavRight');
		obj.data('goto', next);
		obj.removeClass('nodisplay');
	}
	if (prev.length)
	{
		var obj = $('#imgNavLeft');
		obj.data('goto', prev);
		obj.removeClass('nodisplay');
	}

	$('#backdrop').removeClass('nodisplay');
	return false;
}