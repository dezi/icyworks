<!doctype html>
<html>
<head>
<title>ICY-Zeiger</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta http-equiv="imagetoolbar" content="no" />
<meta http-equiv="X-UA-Compatible" content="IE=9" />
<meta name="format-detection" content="telephone=no">
<meta name="MSSmartTagsPreventParsing" content="true" />
<meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<!-------
<link rel="apple-touch-icon-precomposed" type="image/png" href="FUNA.D.1.0/FUNA.Logo.iPhone.A.png" />
<link rel="shortcut icon" type="image/png" href="FUNA.D.1.0/FUNA.Logo.iPhone.A.png" /> 
<link rel="icon" type="image/png" href="FUNA.D.1.0/FUNA.Logo.iPhone.A.png" />
------>
</head>
<body>
<script>

window.ICYNowplayingCallback = function(data)
{
	var raster = document.ICYRaster;
	
	var domax = 1;
	
	var acttitle   = new Object();
	var actchannel = new Object();

	if (window.ICYMainSlot.myitem.play)
	{
		var item = window.ICYMainSlot.myitem;
		
		for (count = 0; count < data.length; count++)
		{
			var play = data[ count ];
			
			if (play.channel == item.play.channel)
			{
				var parts  = play.title.split(' - ');
				var artist = parts[ 0 ];
				var title  = parts[ 1 ];
		
				item.play = play;
		
				item.artist.innerHTML = artist;
				item.title.innerHTML  = title;
		
				item.logo.src  = 'logo/' + play.logo;
				item.cover.src = 'cover/' + play.cover;

				break;
			}
		}
	}
	
	for (count = 0; count < raster.length; count++)
	{
		if (! raster[ count ].play) continue;
		
		acttitle  [ raster[ count ].play.title   ] = true;
		actchannel[ raster[ count ].play.channel ] = true;
	}

	for (count = 0; (count < raster.length) && data.length; count++)
	{
		var play = data.shift();
		
		if (acttitle  [ play.title   ]) continue;
		if (actchannel[ play.channel ]) continue;
				
		var item = raster[ 0 ];
		if (item.play && (domax-- <= 0)) break;
		
		raster.shift();
		
		var parts = play.title.split(' - ');
		var artist = parts[ 0 ];
		var title  = parts[ 1 ];
		
		item.play = play;
		
		item.artist.innerHTML = artist;
		item.title.innerHTML  = title;
		
		item.logo.src  = 'logo/' + play.logo;
		item.cover.src = 'cover/' + play.cover;
		
		raster.push(item);
		
		acttitle  [ play.title   ] = true;
		actchannel[ play.channel ] = true;
	}
	
	window.ICYActScript.parentNode.removeChild(window.ICYActScript);
	window.ICYActScript = false;
	
	window.setTimeout('window.ICYNowplaying()',1000);
}

window.ICYNowplaying = function()
{
	window.ICYActScript = document.createElement('script');
	
	window.ICYActScript.src = '/nowplaying?rnd=' + Math.random();
	document.body.appendChild(window.ICYActScript);
}

window.ICYKaufdat = function(shoplink)
{
	if (! window.ICYMainSlot.myitem.play) return;
	
	shoplink = shoplink.replace("%TITLE%", encodeURI(window.ICYMainSlot.myitem.title.innerHTML ));
	shoplink = shoplink.replace("%ARTIST%",encodeURI(window.ICYMainSlot.myitem.artist.innerHTML));
	
	window.open(shoplink);
}

window.ICYKaufdat1 = function()
{
	url = 'http://clk.tradedoubler.com/click?p=23761&a=1524455'
		+ '&url=http://phobos.apple.com/WebObjects/MZSearch.woa/wa/advancedSearchResults'
		+ '?songTerm=%TITLE%&artistTerm=%ARTIST%&s=143443&partnerId=2003';
		
	window.ICYKaufdat(url);
}

window.ICYKaufdat2 = function()
{
	url = 'http://partners.webmasterplan.com/click.asp'
		+ '?ref=454996&site=3752&type=text&tnb=29&prd=yes&stext=%ARTIST% %TITLE%';
		
	window.ICYKaufdat(url);
}

window.ICYKaufdat3 = function()
{
	url = 'http://www.amazon.de/gp/search?ie=UTF8&keywords=%ARTIST%%20-%20%TITLE%'
		+ '&index=mp3-downloads&linkCode=ur2&camp=1638&creative=6742';
		
	window.ICYKaufdat(url);
}

window.ICYKaufdat4 = function()
{
	url = 'http://www.discogs.com/search?q=%ARTIST%+-+%TITLE%&type=all';
		
	window.ICYKaufdat(url);
}

window.ICYPlaydat = function(event)
{
	var target = (window.event && window.event.srcElement) || event.target;
	
	while (target && ! target.myitem)
	{
		target = target.parentNode;
	}
	
	if (! target) return;
	
	window.ICYMainSlot.myitem.play = target.myitem.play;
	window.ICYMainSlot.myitem.logo.src  = target.myitem.logo.src;
	window.ICYMainSlot.myitem.cover.src = target.myitem.cover.src;
	window.ICYMainSlot.myitem.title.innerHTML  = target.myitem.title.innerHTML;
	window.ICYMainSlot.myitem.artist.innerHTML = target.myitem.artist.innerHTML;
	
	mystream = target.myitem.play.streamurl;
	
	if (window.ICYAudio)
	{
		if (window.ICYPlayer.mystream == mystream)
		{
			window.ICYAudio.pause();
			mystream = false;
		}
		else
		{
			window.ICYAudio.src  = target.myitem.play.streamurl;
			window.ICYAudio.type = 'audio/mpeg';
			window.ICYAudio.play();
			
			window.ICYMainSlot.style.backgroundColor = '#fdd';
		}
	}
	else
	{
		if (window.ICYPlayer.mystream == mystream)
		{
			window.ICYPlayer.innerHTML = '';
			mystream = false;
		}
		else
		{
			if (window.ICYAgent.indexOf('msie') >= 0)
			{
				var html = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="100%" height="100%">'
						 + '<param name="movie" value="player.swf" />'
						 + '<param name="flashvars" value="type=mp3&autostart=true&file='
						 + '/reloc?reloc=' + target.myitem.play.streamurl
						 + '" />'
						 + '</object>'
						 ;
						 
				window.ICYPlayer.innerHTML = html;
				
				window.ICYMainSlot.style.backgroundColor = '#ddf';
			}
			else
			{
				var html = '<object type="application/x-shockwave-flash" data="player.swf" width="100%" height="100%">'
						 + '<param name="flashvars" value="type=mp3&autostart=true&file='
						 + '/reloc?reloc=' + target.myitem.play.streamurl
						 + '" />'
						 + '</object>'
						 ;
						 
				window.ICYPlayer.innerHTML = html;
				
				window.ICYMainSlot.style.backgroundColor = '#dfd';
			}
		}
	}
	
	window.ICYPlayer.mystream = mystream;
}

function ICYMakeSlot(row,col,wid,hei,xmarg,ymarg,text,main)
{
	var div = document.createElement('div');
	
	div.style.position		  = 'absolute';
	div.style.top     		  = ((row * (hei + ymarg)) + ymarg - 4) + 'px';
	div.style.left    		  = ((col * (wid + xmarg)) + xmarg - 4) + 'px';
	
	if (main)
	{
		wid = wid * 2 + xmarg;
		hei = hei * 2 + ymarg;
		
		text += 4;
	}
	
	div.style.width    		  = (wid + 0) + 'px';
	div.style.height   		  = (hei + 0) + 'px';
	div.style.backgroundColor = '#fff';
	div.style.border		  = '1px solid black';
	div.style.fontFamily      = 'sans-serif';
	div.style.fontSize        = (text - 2) + 'px';
	div.style.lineHeight      = (text + 2) + 'px';
	div.style.whiteSpace      = 'nowrap';
	div.onclick			      = window.ICYPlaydat;

	var logo = document.createElement('img');
	logo.style.position	= 'absolute';
	logo.style.top      = '0px';
	logo.style.left     = '0px';
	logo.style.width    = (wid / 2) + 'px';
	logo.style.height   = (wid / 2) + 'px';
	div.appendChild(logo);
	
	var cover = document.createElement('img');
	cover.style.position = 'absolute';
	cover.style.top      = '0px';
	cover.style.left     = (wid / 2) + 'px';
	cover.style.width    = (wid / 2) + 'px';
	cover.style.height   = (wid / 2) + 'px';
	div.appendChild(cover);
	
	var artist = document.createElement('div');
	artist.style.position = 'absolute';
	artist.style.top      = (wid / 2) + 'px';
	artist.style.left     = '0px';
	artist.style.right    = '0px';
	artist.style.height   = text + 'px';
	artist.style.backgroundColor = '#ccc';
	artist.style.fontWeight      = 'bold';
	artist.style.padding         = '3px';
	artist.style.overflow     	 = 'hidden';
	artist.style.textOverflow 	 = 'ellipsis';
	div.appendChild(artist);
	
	var title = document.createElement('div');
	title.style.position = 'absolute';
	title.style.overflow = 'hidden';
	title.style.top      = ((wid / 2) + text + 6) + 'px';
	title.style.left     = '0px';
	title.style.right    = '0px';
	title.style.height   = text + 'px';
	title.style.backgroundColor = '#ddd';
	title.style.fontWeight      = 'normal';
	title.style.padding         = '3px';
	title.style.overflow     	= 'hidden';
	title.style.textOverflow 	= 'ellipsis';
	div.appendChild(title);
	
	if (main)
	{
		var logo1img = document.createElement('img');
		logo1img.src = 'shop.itunes.logo.png';
		
		logo1img.style.position = 'absolute';
		logo1img.style.bottom   = '4px';
		logo1img.style.left     = '10%';
		logo1img.style.width    = '32px';
		logo1img.style.height   = '32px';
		logo1img.onclick		= window.ICYKaufdat1;
		
		div.appendChild(logo1img);
		
		var logo2img = document.createElement('img');
		logo2img.src = 'shop.musicload.logo.png';
		
		logo2img.style.position = 'absolute';
		logo2img.style.bottom   = '4px';
		logo2img.style.left     = '33%';
		logo2img.style.width    = '32px';
		logo2img.style.height   = '32px';
		logo2img.onclick		= window.ICYKaufdat2;

		div.appendChild(logo2img);
		
		var logo3img = document.createElement('img');
		logo3img.src = 'shop.amazon.logo.png';
		
		logo3img.style.position = 'absolute';
		logo3img.style.bottom   = '4px';
		logo3img.style.left     = '56%';
		logo3img.style.width    = '32px';
		logo3img.style.height   = '32px';
		logo3img.onclick		= window.ICYKaufdat3;
		
		div.appendChild(logo3img);
		
		var logo4img = document.createElement('img');
		logo4img.src = 'shop.discogs.logo.png';
		
		logo4img.style.position = 'absolute';
		logo4img.style.bottom   = '4px';
		logo4img.style.left     = '77%';
		logo4img.style.width    = '32px';
		logo4img.style.height   = '32px';
		logo4img.onclick		= window.ICYKaufdat4;
		
		div.appendChild(logo4img);
	}
	
	var item = new Object();
	item.div    = div;
	item.logo   = logo;
	item.cover  = cover;
	item.title  = title;
	item.artist = artist;
	
	div.myitem = item;
	
	return div;
}

var wid   = 160;
var hei   = 124;
var xmarg =  10;
var ymarg =  10;
var cols  =   6;
var rows  =   5;
var text  =  16;
var pcol  = 2;
var prow  = 0;

document.ICYRaster = new Array();

for (var row = 0; row < rows; row++)
{
	for (var col = 0; col < cols; col++)
	{
		if ((prow <= row) && (row <= prow + 1) &&
			(pcol <= col) && (col <= pcol + 1))
		{
			if ((prow == row) && (pcol == col))
			{
				window.ICYMainSlot = ICYMakeSlot(row,col,wid,hei,xmarg,ymarg,text,true);
				document.body.appendChild(window.ICYMainSlot);
			}
			
			continue;
		}
			
		var div = ICYMakeSlot(row,col,wid,hei,xmarg,ymarg,text,false);
		
		document.ICYRaster.push(div.myitem);
		document.body.appendChild(div);
	}
}

for (var inx = 0; inx < document.ICYRaster.length; inx++)
{
	var inx1 = Math.floor(Math.random() * document.ICYRaster.length);
	var inx2 = Math.floor(Math.random() * document.ICYRaster.length);

	var temp = document.ICYRaster[ inx1 ];
	document.ICYRaster[ inx1 ] = document.ICYRaster[ inx2 ];
	document.ICYRaster[ inx2 ] = temp;
}

window.ICYPlayer = document.createElement('div');
window.ICYPlayer.style.position   = 'absolute';
window.ICYPlayer.style.visibility = 'visible';
window.ICYPlayer.style.width      = '200px';
window.ICYPlayer.style.height     = '20px';
window.ICYPlayer.style.top        = '-1000px';
window.ICYPlayer.style.left       = '0px';
document.body.appendChild(window.ICYPlayer);

window.ICYAgent = navigator.userAgent.toLowerCase();

if ((window.ICYAgent.indexOf('android') >= 0) ||
	(window.ICYAgent.indexOf('iphone') >= 0) ||
	(window.ICYAgent.indexOf('ipad') >= 0))
{
	window.ICYAudio = document.createElement('audio');
	window.ICYAudio.autobuffer = true;
	window.ICYAudio.autoplay   = true;
	window.ICYPlayer.appendChild(window.ICYAudio);
}

window.ICYNowplaying();

</script>
</body>
</html>
