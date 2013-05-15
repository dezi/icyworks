<?php

include "../php/json.php";
include "../php/util.php";

	comp_artist("");
	
	$workdir = "../var/pending";
	$cached  = get_directory("../var/cached");
	$compare = Array();
	
	foreach ($cached as $index => $title)
	{
		$compare[ $index ] = comp_title(substr($title,0,-5));
	}

	$pending = get_directory($workdir);
		
	foreach ($pending as $track)
	{
		$title = comp_title(substr($track,0,-5));
		
		if (strstr($title," - ") === false)
		{
			unlink("$workdir/$track");
			continue;
		}
		
		if (preg_match("/^[0-9][0-9]/",$title)) 
		{
			unlink("$workdir/$track");
			continue;
		}
		
		foreach ($compare as $index => $known)
		{
			if (comp_levenshtein($title,$known))
			{
				$oldfile = "../var/cached/" . $cached[ $index ];
				$oldjson = file_get_contents($oldfile);
				if ($oldjson === false) continue;
				
				$newfile = "../var/cached/" . $track;
				file_put_contents($newfile,$oldjson);
				unlink("$workdir/$track");
				
				echo "$track\n";
				echo $cached[ $index ] . "\n";
				echo "------\n";
				break;
			}
		}
	}
	
?>