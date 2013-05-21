<!doctype html>
<html>
<head>
<title>ICY-Zeiger</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta http-equiv="imagetoolbar" content="no" />
<meta http-equiv="X-UA-Compatible" content="IE=8" />
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

	if (window.ICYActItem)
	{
		var item = window.ICYActItem.myitem;
		
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

	for (count = 0; count < raster.length; count++)
	{
		var play = data.shift();
		
		if (window.ICYActItem == raster[ 0 ].div)
		{
			raster.push(raster.shift());
			continue;
		}
		
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
	
	window.setTimeout('window.ICYNowplaying()',1000);
}

window.ICYNowplaying = function()
{
	var script = document.createElement('script');
	
	script.src = '/nowplaying?rnd=' + Math.random();
	document.body.appendChild(script);
}

window.ICYPlaydat = function(event)
{
	var target = event.target;
	
	while (target && ! target.myitem)
	{
		target = target.parentNode;
	}
	
	if (! target) return;
	
	if (window.ICYActItem)
	{
		window.ICYActItem.style.border = '1px solid black';
	}
	
	target.style.border = '1px solid red';
	window.ICYActItem = target;
	
	window.ICYAudio.setAttribute('src',target.myitem.play.streamurl);
	window.ICYAudio.play();
}

var wid   = 160;
var hei   = 124;
var xmarg =  10;
var ymarg =  10;
var cols  =   6;
var rows  =   5;
var size  = wid / 2;
var text  =  16;

document.ICYRaster = new Array();

for (var row = 0; row < rows; row++)
{
	for (var col = 0; col < cols; col++)
	{
		var div = document.createElement('div');
		
		div.style.position		  = 'absolute';
		div.style.top     		  = ((row * (hei + ymarg)) + ymarg - 4) + 'px';
		div.style.left    		  = ((col * (wid + xmarg)) + xmarg - 4) + 'px';
		div.style.width    		  = (wid + 0) + 'px';
		div.style.height   		  = (hei + 0) + 'px';
		div.style.backgroundColor = '#fff';
		div.style.border		  = '1px solid black';
		div.style.fontFamily      = 'sans-serif';
		div.style.fontSize        = '14px';
		div.style.lineHeight      = '18px';
		div.style.whiteSpace      = 'nowrap';
		div.onclick			      = window.ICYPlaydat;

		var logo = document.createElement('img');
		logo.style.position	= 'absolute';
		logo.style.top      = '0px';
		logo.style.left     = '0px';
		logo.style.width    = size + 'px';
		logo.style.height   = size + 'px';
		div.appendChild(logo);
		
		var cover = document.createElement('img');
		cover.style.position = 'absolute';
		cover.style.top      = '0px';
		cover.style.left     = size + 'px';
		cover.style.width    = size + 'px';
		cover.style.height   = size + 'px';
		div.appendChild(cover);
		
		var artist = document.createElement('div');
		artist.style.position = 'absolute';
		artist.style.top      = size + 'px';
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
		title.style.top      = (size + text + 6) + 'px';
		title.style.left     = '0px';
		title.style.right    = '0px';
		title.style.height   = text + 'px';
		title.style.backgroundColor = '#ddd';
		title.style.fontWeight      = 'normal';
		title.style.padding         = '3px';
		title.style.overflow     	= 'hidden';
		title.style.textOverflow 	= 'ellipsis';
		div.appendChild(title);
		
		var item = new Object();
		item.div    = div;
		item.logo   = logo;
		item.cover  = cover;
		item.title  = title;
		item.artist = artist;
		
		div.myitem = item;
		document.ICYRaster.push(item);
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

window.ICYAudio = document.createElement('audio');
window.ICYAudio.setAttribute('type','audio/mpeg');
document.body.appendChild(window.ICYAudio);

window.ICYNowplaying();

</script>
</body>
</html>
