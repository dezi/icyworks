<?php

include "../php/util.php";
include "../php/json.php";

function loadimage($url,$file,$orig)
{
	$parts = explode(".",$url);
	$fext  = array_pop($parts);
	$orig  = "$orig.$fext";
	
	if (file_exists($file)) return;
	
	echo "$url $file\n";

	if (file_exists($orig))
	{
		$data = file_get_contents($orig);
	}
	else
	{
		$data = file_get_contents($url);
	
		if ($data === false) return;

		file_put_contents("$orig.$fext",$data);
	}
	
	$image = imagecreatefromstring($data);
	
	if ($image === false) return;
	
	$wid = imagesx($image);
	$hei = imagesy($image);
	
	echo "$wid x $hei\n";
	
	$newi = imagecreatetruecolor(167,167);
	
	imagecopy($newi,$image,0,0,4,4,167,167);
	
	imagepng($newi,$file);
}

	$channels = get_directory("../etc/channels");
	
	foreach ($channels as $channel)
	{
		$jsonpath = "../etc/channels/$channel/$channel.json";
		$jsondata = json_decdat(file_get_contents($jsonpath));
		
		if (! isset($jsondata[ "broadcast" ][ "picture4Url" ])) continue;
		
		$url = $jsondata[ "broadcast" ][ "picture4Url" ];
		$file = "../etc/logos/$channel.167x167.png";				
		$orig = "../etc/logos/$channel.orig";				

		loadimage($url,$file,$orig);
		
		//exit();
	}
	
	 
//loadimage("http://static.radio.de/images/broadcasts/9804_4.gif");
//loadimage("http://static.radio.de/images/broadcasts/9451_4.jpeg");



?>