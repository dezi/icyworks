<?php

include "json.php";
include "util.php";


	$artists = Array();
	$counter = 0;
	
	$dd = opendir("../var/cached");
 
	while (($file = readdir($dd)) !== false)
	{
		if ($file == ".") continue;
		if ($file == "..") continue;

		$counter++;
		
		$title_orig  = substr($file,0,-5);
		$title_final = substr($file,0,-5);
		
		if (! make_final($title_final))
		{
			echo "Schrott: $title_orig\n";
			
			continue;
		}
	
		$parts = explode(" - ",$title_final);
		$artists[ $parts[ 0 ] ] = true;
	}
	
	closedir($dd);
	
	ksort($artists);
	
	$fd = fopen("./finalname.txt","w");
	
	foreach ($artists as $name => $count)
	{
		fputs($fd,$name . "\n");
	}
	
	fclose($fd);
	
	echo "Total: " . $counter . "\n";
	echo "Artists: " . count($artists) . "\n";
?>